<?php

namespace App\Services\Elternabfragen;

use App\Enums\GuardianRight;
use App\Enums\InquiryAssignmentStatus;
use App\Enums\InquiryFieldType;
use App\Enums\InquiryReceiptKind;
use App\Enums\InquiryReviewStatus;
use App\Model\Child;
use App\Model\InquiryAssignment;
use App\Model\InquiryDraft;
use App\Model\InquiryEvent;
use App\Model\InquiryResponse;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use App\Settings\ElternabfragenSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Prozess C: Entwurf speichern, verbindlich absenden, korrigieren.
 * Jede Einreichung erzeugt eine unveränderliche Antwortversion mit Hashes.
 */
class ResponseService
{
    public const DISK = 'local';

    public function __construct(
        private readonly FieldSchema $schema,
        private readonly InquiryAccess $access,
        private readonly DeliveryService $delivery,
        private readonly ElternabfragenSetting $settings,
        private readonly FamilyResolver $family,
        private readonly PreviousAnswers $previousAnswers,
        private readonly ReceiptService $receipts,
    ) {}

    public function fields(InquiryAssignment $assignment): array
    {
        return $assignment->inquiry->templateVersion->fields ?? [];
    }

    /**
     * Wählbare Kinder für Felder vom Typ „child“: alle aktiven Kinder, für die das Konto
     * sorgeberechtigt ist (id => Name). Ohne solche Felder keine Abfrage.
     *
     * @return array<int, string>
     */
    public function childOptions(array $fields, User $user): array
    {
        if (! collect($fields)->contains(fn ($f) => ($f['type'] ?? null) === InquiryFieldType::Child->value)) {
            return [];
        }

        return $this->family->childrenFor($user, GuardianRight::Custody)
            ->filter(fn ($child) => $child->status === null || $child->status === Child::STATUS_ACTIVE)
            ->sortBy(fn ($child) => $child->first_name.' '.$child->last_name)
            ->mapWithKeys(fn ($child) => [$child->id => trim($child->first_name.' '.$child->last_name)])
            ->all();
    }

    /**
     * Entwurf je Konto – gilt nicht als Rückmeldung. Dateien werden erst beim Absenden übernommen.
     */
    public function saveDraft(InquiryAssignment $assignment, User $user, array $input): InquiryDraft
    {
        $state = $this->access->state($user, $assignment);
        if (! $state['can_submit'] && ! $state['can_correct']) {
            throw new HttpException(403, $state['reason'] ?? 'Keine Berechtigung.');
        }

        $fields = $this->fields($assignment);
        $children = $this->childOptions($fields, $user);
        Validator::make(['answers' => $input], $this->schema->rules($fields, false, $this->settings->upload_max_kb, [], $children), $this->schema->messages($fields))
            ->validate();

        $draft = InquiryDraft::updateOrCreate(
            ['assignment_id' => $assignment->id, 'user_id' => $user->id],
            ['answers' => $this->schema->sanitize($fields, $input, $children)],
        );

        if (! $assignment->isAnswered()) {
            $assignment->refreshStatus();
        }

        return $draft;
    }

    public function discardDraft(InquiryAssignment $assignment, User $user): void
    {
        InquiryDraft::where('assignment_id', $assignment->id)->where('user_id', $user->id)->delete();
        if (! $assignment->isAnswered()) {
            $assignment->refreshStatus();
        }
    }

    /**
     * Verbindlich absenden bzw. korrigieren.
     *
     * @param  array<string, mixed>  $input  Antworten (ohne Dateien)
     * @param  array<string, UploadedFile>  $files  Dateien je Feldschlüssel
     * @param  array{channel?: string, ip?: ?string, user_agent?: ?string}  $meta
     *
     * @throws ValidationException
     */
    public function submit(InquiryAssignment $assignment, User $user, array $input, array $files, array $meta = [], ?string $correctionReason = null): InquiryResponse
    {
        // Rechte vor der Validierung prüfen (sonst 422 statt 403/409 für Unberechtigte)
        $state = $this->access->state($user, $assignment);
        if ($assignment->isAnswered() ? ! $state['can_correct'] : ! $state['can_submit']) {
            throw new HttpException($assignment->isAnswered() && $state['can_view_answers'] ? 409 : 403, $state['reason'] ?? 'Keine Berechtigung.');
        }

        $fields = $this->fields($assignment);
        $previous = $assignment->currentResponse;
        $existing = $previous?->answers ?? [];
        $children = $this->childOptions($fields, $user);

        Validator::make(
            ['answers' => $files + $input],
            $this->schema->rules($fields, true, $this->settings->upload_max_kb, $existing, $children),
            $this->schema->messages($fields),
        )->validate();

        $response = DB::transaction(function () use ($assignment, $user, $input, $files, $meta, $correctionReason, $fields, $children) {
            /** @var InquiryAssignment $locked */
            $locked = InquiryAssignment::query()->whereKey($assignment->id)->lockForUpdate()->first();
            $locked->setRelation('inquiry', $assignment->inquiry);
            $locked->setRelation('child', $assignment->child);

            $state = $this->access->state($user, $locked);
            $isCorrection = $locked->isAnswered();
            if ($isCorrection ? ! $state['can_correct'] : ! $state['can_submit']) {
                throw new HttpException($isCorrection ? 409 : 403, $isCorrection
                    ? 'Für dieses Kind liegt bereits eine Antwort vor. '.($state['reason'] ?? '')
                    : ($state['reason'] ?? 'Keine Berechtigung.'));
            }

            $previous = $locked->current_response_id ? InquiryResponse::find($locked->current_response_id) : null;
            $answers = $this->schema->sanitize($fields, $input, $children);
            $answers += $this->storeFiles($locked, $fields, $files, $previous?->answers ?? []);

            $response = $this->createVersion($locked, $user, $answers, $meta, $correctionReason);
            InquiryDraft::where('assignment_id', $locked->id)->where('user_id', $user->id)->delete();

            return $response;
        });

        $assignment->refresh();
        $this->delivery->confirm($response->setRelation('assignment', $assignment));

        return $response;
    }

    /**
     * Neue gültige Antwortversion anlegen (Aufruf innerhalb einer Transaktion mit gesperrtem Auftrag).
     * Wird beim Absenden durch Eltern und bei der Übernahme einer Änderungsmeldung verwendet.
     * Stellt in derselben Transaktion die Erklärungsquittung aus (Relation „receipt“ der Rückgabe).
     *
     * @param  array<string, mixed>  $answers  bereinigte Antworten inkl. Dateiangaben
     * @param  array{channel?: string, ip?: ?string, user_agent?: ?string}  $meta
     * @param  array{review_status?: InquiryReviewStatus, reviewed_by?: ?int, reviewed_at?: mixed, review_note?: ?string}  $review
     */
    public function createVersion(InquiryAssignment $locked, User $user, array $answers, array $meta, ?string $reason, array $review = [], string $action = ''): InquiryResponse
    {
        $inquiry = $locked->inquiry;
        $fields = $this->fields($locked);
        $previous = $locked->current_response_id ? InquiryResponse::find($locked->current_response_id) : null;
        $baseline = $this->previousAnswers->baseline($locked);

        $response = InquiryResponse::create([
            'assignment_id' => $locked->id,
            'inquiry_id' => $inquiry->id,
            'child_id' => $locked->child_id,
            'user_id' => $user->id,
            'template_version_id' => $inquiry->template_version_id,
            // Nach einem Neustart beginnt keine neue Zählung: Versionen bleiben je Auftrag eindeutig
            'version' => (int) InquiryResponse::where('assignment_id', $locked->id)->max('version') + 1,
            'answers' => $answers,
            'form_hash' => $this->schema->formHash($inquiry->template_version_id, $inquiry->title, $inquiry->intro, $fields),
            'answer_hash' => $this->schema->answerHash($answers),
            'previous_response_id' => $previous?->id,
            'baseline_response_id' => $baseline?->id,
            'baseline_changes' => $baseline ? $this->previousAnswers->changes($fields, $baseline, $answers) : null,
            'correction_reason' => $previous ? (Str::limit(trim((string) $reason), 1000, '') ?: null) : null,
            'is_current' => true,
            'channel' => in_array($meta['channel'] ?? null, ['web', 'app'], true) ? $meta['channel'] : 'web',
            'ip_anonymized' => self::anonymizeIp($meta['ip'] ?? null),
            'user_agent' => isset($meta['user_agent']) ? Str::limit((string) $meta['user_agent'], 250, '') : null,
            'submitted_at' => now(),
        ] + $review + ['review_status' => InquiryReviewStatus::Pending]);

        $previous?->update(['is_current' => false]);

        $locked->forceFill([
            'current_response_id' => $response->id,
            'first_submitted_at' => $locked->first_submitted_at ?? $response->submitted_at,
            'last_submitted_at' => $response->submitted_at,
            'has_problem' => false,
            'status' => $previous ? InquiryAssignmentStatus::Corrected : InquiryAssignmentStatus::Submitted,
        ])->save();
        // Ggf. „Bestätigung ausstehend“, wenn weitere Sorgeberechtigte bestätigen müssen
        $locked->refreshStatus();

        InquiryEvent::create([
            'inquiry_id' => $inquiry->id,
            'assignment_id' => $locked->id,
            'user_id' => $user->id,
            'action' => $action ?: ($previous ? 'response.corrected' : 'response.submitted'),
            'note' => $response->correction_reason,
            'data' => ['version' => $response->version, 'answer_hash' => $response->answer_hash],
        ]);

        $kind = match (true) {
            $action === 'response.changed' => InquiryReceiptKind::ChangeAccepted,
            $action === 'response.withdrawn' => InquiryReceiptKind::Withdrawal,
            $previous !== null => InquiryReceiptKind::Correction,
            default => InquiryReceiptKind::Submission,
        };
        $response->setRelation('receipt', $this->receipts->issue($response, $kind, null, ['declarant' => $user]));

        return $response;
    }

    /**
     * Einwilligungen der gültigen Antwort, die widerrufen werden können (Feldtyp consent, Wert
     * „granted“), als Schlüssel => Beschriftung.
     *
     * @return array<string, string>
     */
    public function withdrawableFields(InquiryAssignment $assignment): array
    {
        $answers = $assignment->current_response_id !== null
            ? (InquiryResponse::whereKey($assignment->current_response_id)->first()?->answers ?? [])
            : [];

        return collect($this->fields($assignment))
            ->filter(fn (array $f) => ($f['type'] ?? null) === InquiryFieldType::Consent->value
                && ($answers[$f['key']] ?? null) === FieldSchema::GRANTED)
            ->mapWithKeys(fn (array $f) => [$f['key'] => (string) ($f['label'] ?? $f['key'])])
            ->all();
    }

    /**
     * Widerruf aus Sicht des Nutzers: möglich für Sorgeberechtigte, solange die Antworten nicht
     * gelöscht sind – unabhängig von Frist und Korrekturfenster (Art. 7 Abs. 3 DSGVO).
     *
     * @return array{possible: bool, fields: list<array{key: string, label: string}>}
     */
    public function withdrawalState(User $user, InquiryAssignment $assignment): array
    {
        if (! $this->access->canAnswer($user, $assignment) || $assignment->inquiry?->purged_at !== null) {
            return ['possible' => false, 'fields' => []];
        }

        $fields = [];
        foreach ($this->withdrawableFields($assignment) as $key => $label) {
            $fields[] = ['key' => (string) $key, 'label' => $label];
        }

        return ['possible' => $fields !== [], 'fields' => $fields];
    }

    /**
     * Einwilligung(en) widerrufen (Art. 7 Abs. 3 DSGVO): neue Antwortversion, in der die
     * genannten Einwilligungen „denied“ sind, mit genau einer Quittung der Art „withdrawal“
     * (Relation „receipt“ der Rückgabe). Jederzeit möglich – auch nach Fristende, ohne
     * Korrekturfenster und bei abgeschlossener Abfrage –, aber nicht nach der Löschung.
     * Bei gemeinsamer Erklärung wirkt der Widerruf sofort (siehe GuardianConfirmation).
     *
     * @param  list<string>  $fieldKeys  leer = alle widerrufbaren Einwilligungen
     * @param  array{ip?: ?string, user_agent?: ?string}  $meta
     *
     * @throws ValidationException bei nicht widerrufbaren Feldern
     */
    public function withdraw(InquiryAssignment $assignment, User $user, array $fieldKeys, ?string $reason, string $channel, array $meta = []): InquiryResponse
    {
        if (! $this->access->canAnswer($user, $assignment)) {
            throw new HttpException(403, 'Einwilligungen können nur sorgeberechtigte Personen widerrufen.');
        }
        if ($assignment->inquiry?->purged_at !== null) {
            throw new HttpException(403, 'Die Antworten zu dieser Abfrage wurden bereits gelöscht.');
        }

        $response = DB::transaction(function () use ($assignment, $user, $fieldKeys, $reason, $channel, $meta) {
            /** @var InquiryAssignment $locked */
            $locked = InquiryAssignment::query()->whereKey($assignment->id)->lockForUpdate()->first();
            $locked->setRelation('inquiry', $assignment->inquiry);
            $locked->setRelation('child', $assignment->child);

            $available = $this->withdrawableFields($locked);
            if ($available === []) {
                throw ValidationException::withMessages(['fields' => 'Es liegt keine erteilte Einwilligung vor, die widerrufen werden kann.']);
            }
            $keys = $fieldKeys === [] ? array_keys($available) : array_values(array_unique(array_map('strval', $fieldKeys)));
            if (array_diff($keys, array_keys($available)) !== []) {
                throw ValidationException::withMessages(['fields' => 'Mindestens eine der gewählten Angaben ist keine erteilte Einwilligung und kann nicht widerrufen werden.']);
            }

            $answers = InquiryResponse::findOrFail($locked->current_response_id)->answers ?? [];
            foreach ($keys as $key) {
                $answers[$key] = FieldSchema::DENIED;
            }

            $reason = Str::limit(trim((string) $reason), 500, '');
            $response = $this->createVersion(
                $locked,
                $user,
                $answers,
                ['channel' => $channel] + $meta,
                'Widerruf der Einwilligung'.($reason !== '' ? ': '.$reason : ''),
                [],
                'response.withdrawn',
            );
            // Erst mit der Quittung ist die Version als Widerruf erkennbar: Bestätigungsstand neu ableiten
            $locked->refreshStatus();

            return $response;
        });

        $assignment->refresh();
        $this->delivery->withdrawn($response->setRelation('assignment', $assignment));

        return $response;
    }

    /**
     * Antworten für die Anzeige: Entwurf des Nutzers vor aktueller Antwort vor Angaben der Vorabfrage.
     */
    public function prefill(InquiryAssignment $assignment, User $user): array
    {
        $draft = InquiryDraft::where('assignment_id', $assignment->id)->where('user_id', $user->id)->first();

        return $draft?->answers ?? $assignment->currentResponse?->answers ?? $this->prefillFromPrevious($assignment, $user);
    }

    /**
     * Vorbefüllung aus der Vorabfrage (nur solange noch keine Antwort vorliegt).
     *
     * @return array<string, mixed>
     */
    public function prefillFromPrevious(InquiryAssignment $assignment, User $user): array
    {
        if ($assignment->isAnswered()) {
            return [];
        }
        $fields = $this->fields($assignment);

        // Neu gestarteter Fall: letzte Antwort vorbefüllen (Dateien müssen neu hochgeladen werden)
        $last = InquiryResponse::where('assignment_id', $assignment->id)->orderByDesc('version')->first();
        if ($last !== null) {
            $files = collect($fields)->filter(fn (array $f) => $f['type'] === InquiryFieldType::File->value)->pluck('key')->all();

            return array_diff_key($last->answers ?? [], array_flip($files));
        }

        return $this->previousAnswers->prefill($assignment, $fields, $this->childOptions($fields, $user));
    }

    public function filePath(InquiryResponse $response, string $key): ?array
    {
        $value = $response->answers[$key] ?? null;

        return is_array($value) && isset($value['path']) && Storage::disk(self::DISK)->exists($value['path']) ? $value : null;
    }

    /**
     * @return array<string, array{name: string, path: string, size: int, mime: ?string}>
     */
    public function storeFiles(InquiryAssignment $assignment, array $fields, array $files, array $previous): array
    {
        $stored = [];
        foreach ($fields as $field) {
            if (InquiryFieldType::from($field['type']) !== InquiryFieldType::File) {
                continue;
            }
            $key = $field['key'];
            $file = $files[$key] ?? null;

            if ($file instanceof UploadedFile) {
                $path = $file->storeAs(
                    'elternabfragen/'.$assignment->inquiry_id.'/'.$assignment->id,
                    Str::uuid()->toString().'.'.strtolower($file->getClientOriginalExtension() ?: $file->extension()),
                    self::DISK,
                );
                $stored[$key] = [
                    'name' => Str::limit($file->getClientOriginalName(), 200, ''),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];
            } elseif (! empty($previous[$key])) {
                // Korrektur ohne neue Datei: bisherige Datei bleibt Teil der neuen Version
                $stored[$key] = $previous[$key];
            } else {
                $stored[$key] = null;
            }
        }

        return $stored;
    }

    /** Technische Nachweisdaten im zulässigen Umfang: letzte Stelle der IP wird entfernt. */
    public static function anonymizeIp(?string $ip): ?string
    {
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }

        $packed = inet_pton($ip);

        return inet_ntop(substr($packed, 0, 6).str_repeat("\0", 10));
    }
}

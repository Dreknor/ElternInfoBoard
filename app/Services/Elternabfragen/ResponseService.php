<?php

namespace App\Services\Elternabfragen;

use App\Enums\InquiryAssignmentStatus;
use App\Enums\InquiryFieldType;
use App\Enums\InquiryReviewStatus;
use App\Model\InquiryAssignment;
use App\Model\InquiryDraft;
use App\Model\InquiryEvent;
use App\Model\InquiryResponse;
use App\Model\User;
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
    ) {}

    public function fields(InquiryAssignment $assignment): array
    {
        return $assignment->inquiry->templateVersion->fields ?? [];
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
        Validator::make(['answers' => $input], $this->schema->rules($fields, false, $this->settings->upload_max_kb), $this->schema->messages($fields))
            ->validate();

        $draft = InquiryDraft::updateOrCreate(
            ['assignment_id' => $assignment->id, 'user_id' => $user->id],
            ['answers' => $this->schema->sanitize($fields, $input)],
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

        Validator::make(
            ['answers' => $files + $input],
            $this->schema->rules($fields, true, $this->settings->upload_max_kb, $existing),
            $this->schema->messages($fields),
        )->validate();

        $response = DB::transaction(function () use ($assignment, $user, $input, $files, $meta, $correctionReason, $fields) {
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
            $answers = $this->schema->sanitize($fields, $input);
            $answers += $this->storeFiles($locked, $fields, $files, $previous?->answers ?? []);

            $inquiry = $assignment->inquiry;
            $response = InquiryResponse::create([
                'assignment_id' => $locked->id,
                'inquiry_id' => $inquiry->id,
                'child_id' => $locked->child_id,
                'user_id' => $user->id,
                'template_version_id' => $inquiry->template_version_id,
                'version' => ($previous?->version ?? 0) + 1,
                'answers' => $answers,
                'form_hash' => $this->schema->formHash($inquiry->template_version_id, $inquiry->title, $inquiry->intro, $fields),
                'answer_hash' => $this->schema->answerHash($answers),
                'previous_response_id' => $previous?->id,
                'correction_reason' => $previous ? (Str::limit(trim((string) $correctionReason), 1000, '') ?: null) : null,
                'is_current' => true,
                'channel' => in_array($meta['channel'] ?? null, ['web', 'app'], true) ? $meta['channel'] : 'web',
                'ip_anonymized' => $this->anonymizeIp($meta['ip'] ?? null),
                'user_agent' => isset($meta['user_agent']) ? Str::limit((string) $meta['user_agent'], 250, '') : null,
                'submitted_at' => now(),
                'review_status' => InquiryReviewStatus::Pending,
            ]);

            $previous?->update(['is_current' => false]);

            $locked->forceFill([
                'current_response_id' => $response->id,
                'first_submitted_at' => $locked->first_submitted_at ?? $response->submitted_at,
                'last_submitted_at' => $response->submitted_at,
                'has_problem' => false,
                'status' => $previous ? InquiryAssignmentStatus::Corrected : InquiryAssignmentStatus::Submitted,
            ])->save();

            InquiryDraft::where('assignment_id', $locked->id)->where('user_id', $user->id)->delete();

            InquiryEvent::create([
                'inquiry_id' => $inquiry->id,
                'assignment_id' => $locked->id,
                'user_id' => $user->id,
                'action' => $previous ? 'response.corrected' : 'response.submitted',
                'note' => $response->correction_reason,
                'data' => ['version' => $response->version, 'answer_hash' => $response->answer_hash],
            ]);

            return $response;
        });

        $assignment->refresh();
        $this->delivery->confirm($response->setRelation('assignment', $assignment));

        return $response;
    }

    /**
     * Antworten für die Anzeige: Entwurf des Nutzers vor aktueller Antwort.
     */
    public function prefill(InquiryAssignment $assignment, User $user): array
    {
        $draft = InquiryDraft::where('assignment_id', $assignment->id)->where('user_id', $user->id)->first();

        return $draft?->answers ?? $assignment->currentResponse?->answers ?? [];
    }

    public function filePath(InquiryResponse $response, string $key): ?array
    {
        $value = $response->answers[$key] ?? null;

        return is_array($value) && isset($value['path']) && Storage::disk(self::DISK)->exists($value['path']) ? $value : null;
    }

    /**
     * @return array<string, array{name: string, path: string, size: int, mime: ?string}>
     */
    private function storeFiles(InquiryAssignment $assignment, array $fields, array $files, array $previous): array
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
    private function anonymizeIp(?string $ip): ?string
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

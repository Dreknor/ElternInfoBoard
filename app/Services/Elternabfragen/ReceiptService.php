<?php

namespace App\Services\Elternabfragen;

use App\Enums\InquiryFieldType;
use App\Enums\InquiryReceiptKind;
use App\Events\Elternabfragen\ReceiptIssued;
use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Model\InquiryConfirmation;
use App\Model\InquiryReceipt;
use App\Model\InquiryResponse;
use App\Model\InquiryTemplateVersion;
use App\Model\User;
use App\Settings\GeneralSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Erklärungsquittungen (§ 126b BGB).
 *
 * Für jede Erklärung entsteht ein kleiner, unveränderlicher Datensatz mit Prüfsummen über
 * den vollständigen Wortlaut, die Antworten und die Quittung selbst. Die Quittungen einer
 * Abfrage sind über prev_hash verkettet. Das PDF wird nicht gespeichert: Es lässt sich
 * jederzeit byteidentisch aus Quittung, Vorlagenversion, Abfrage und Antwortversion erzeugen
 * (renderPdf); gespeichert wird nur seine SHA-256 (pdf_sha256).
 */
class ReceiptService
{
    /** Layout des Quittungs-PDFs. Bei Änderungen an View oder Darstellung erhöhen. */
    public const LAYOUT_VERSION = 1;

    /** Feste Zeitzone der Darstellung (unabhängig von späteren Einstellungen). */
    public const TIMEZONE = 'Europe/Berlin';

    private const VIEW = 'elternabfragen.pdf.receipt';

    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

    public function __construct(private readonly FieldSchema $schema) {}

    /**
     * Quittung ausstellen – innerhalb der Transaktion der Erklärung aufrufen, damit Erklärung
     * und Quittung nur gemeinsam entstehen. Nach dem Commit wird die PDF-Prüfsumme ermittelt
     * und das Ereignis ReceiptIssued ausgelöst.
     *
     * Ohne Vorgaben in $options stammen erklärende Person, Zeitpunkt und Kanal aus der
     * Bestätigung bzw. der Antwortversion (Phase 2: Widerruf und Nacherfassung geben sie vor).
     *
     * @param  array{declarant?: ?User, declarant_name?: string, declared_at?: CarbonInterface, channel?: string, backfilled?: bool}  $options
     */
    public function issue(InquiryResponse $response, InquiryReceiptKind $kind, ?InquiryConfirmation $confirmation = null, array $options = []): InquiryReceipt
    {
        return DB::transaction(function () use ($response, $kind, $confirmation, $options) {
            // Kette je Abfrage serialisieren (Sperrreihenfolge überall: Auftrag → Abfrage)
            /** @var Inquiry $inquiry */
            $inquiry = Inquiry::withTrashed()->whereKey($response->inquiry_id)->lockForUpdate()->firstOrFail();
            $last = InquiryReceipt::where('inquiry_id', $inquiry->id)->orderByDesc('sequence')->first(['sequence', 'content_hash']);

            $version = InquiryTemplateVersion::findOrFail($response->template_version_id);
            $assignment = InquiryAssignment::with(['child' => fn ($q) => $q->withTrashed()])->findOrFail($response->assignment_id);

            $declarant = array_key_exists('declarant', $options)
                ? $options['declarant']
                : ($confirmation ? $confirmation->user : $response->user);
            $declaredAt = $options['declared_at'] ?? ($confirmation?->confirmed_at ?? $response->submitted_at);
            $channel = $options['channel'] ?? ($confirmation?->channel ?? $response->channel);

            $receipt = new InquiryReceipt([
                'uuid' => (string) Str::uuid(),
                'inquiry_id' => $inquiry->id,
                'sequence' => ($last?->sequence ?? 0) + 1,
                'assignment_id' => $assignment->id,
                'response_id' => $response->id,
                'confirmation_id' => $confirmation?->id,
                'template_version_id' => $version->id,
                'response_version' => (int) $response->version,
                'kind' => $kind,
                'user_id' => $declarant?->id,
                'declarant_name' => Str::limit($options['declarant_name'] ?? $declarant?->name ?? 'unbekannt', 250, ''),
                'child_name' => Str::limit($assignment->childName(), 250, ''),
                'school_name' => Str::limit($this->schoolName(), 250, ''),
                'channel' => in_array($channel, ['web', 'app', 'mail', 'paper'], true) ? $channel : 'web',
                'declared_at' => self::storedTime($declaredAt),
                'retention_days' => $inquiry->retention_days,
                'attachments' => $this->attachments($version, $response->answers ?? []),
                'form_hash' => $response->form_hash,
                'answer_hash' => $this->schema->answerHash($response->answers ?? []),
                'prev_hash' => $last?->content_hash,
                'layout_version' => self::LAYOUT_VERSION,
                'backfilled' => (bool) ($options['backfilled'] ?? false),
            ]);
            $receipt->wording_hash = $this->wordingHash($inquiry, $version, $receipt->retention_days);
            $receipt->content_hash = $this->contentHash($receipt);
            $receipt->save();

            DB::afterCommit(fn () => $this->afterIssue($receipt));

            return $receipt;
        });
    }

    /**
     * PDF der Quittung erzeugen. Deterministisch: Nur Quittung, Vorlagenversion, Abfrage und
     * Antwortversion fließen ein (keine aktuelle Uhrzeit, keine Einstellungen). Original und
     * spätere Zweitschrift sind byteidentisch.
     */
    public function renderPdf(InquiryReceipt $receipt): string
    {
        $pdf = Pdf::loadHTML($this->html($receipt))->setPaper('a4', 'portrait');
        $pdf->render();

        $dompdf = $pdf->getDomPDF();
        $date = 'D:'.$receipt->declared_at->copy()->utc()->format('YmdHis').'Z';
        $dompdf->addInfo('CreationDate', $date);
        $dompdf->addInfo('ModDate', $date);
        $dompdf->addInfo('Producer', 'ElternInfoBoard');
        $dompdf->addInfo('Title', 'Erklärungsquittung '.$receipt->uuid);
        // Sonst zufällig (microtime/mt_rand) – aus der Inhaltsprüfsumme ableiten
        $dompdf->getCanvas()->get_cpdf()->fileIdentifier = substr($receipt->content_hash, 0, 32);

        return $pdf->output();
    }

    /** HTML der Quittung (Grundlage des PDFs). */
    public function html(InquiryReceipt $receipt): string
    {
        return view(self::VIEW, $this->viewData($receipt))->render();
    }

    /** Quittung zu einem vorgelegten PDF (Vergleich mit der gespeicherten Prüfsumme). */
    public function matchesPdf(string $bytes): ?InquiryReceipt
    {
        return InquiryReceipt::where('pdf_sha256', hash('sha256', $bytes))->first();
    }

    /**
     * Maßgebliche Quittung eines Kontos zur gültigen Antwort: eigene Erklärung (Abgabe,
     * Korrektur oder Bestätigung), sonst die Quittung der Antwortversion.
     */
    public function currentReceiptFor(InquiryAssignment $assignment, User $user): ?InquiryReceipt
    {
        if ($assignment->current_response_id === null) {
            return null;
        }

        $query = fn () => InquiryReceipt::where('response_id', $assignment->current_response_id)
            ->where('kind', '!=', InquiryReceiptKind::Withdrawal->value)
            ->orderByDesc('id');

        return $query()->where('user_id', $user->id)->first()
            ?? $query()->whereNull('confirmation_id')->first();
    }

    /**
     * Kette einer Abfrage prüfen: Reihenfolge ohne Lücken, Verkettung, Inhaltsprüfsummen,
     * Wortlaut, Formular, Antworten und – soweit noch vorhanden – hochgeladene Dateien.
     *
     * @return array{valid: bool, count: int, errors: list<string>}
     */
    public function verifyChain(Inquiry $inquiry): array
    {
        $receipts = InquiryReceipt::where('inquiry_id', $inquiry->id)->orderBy('sequence')->with('response')->get();
        $inquiry = Inquiry::withTrashed()->findOrFail($inquiry->id);
        $versions = InquiryTemplateVersion::whereIn('id', $receipts->pluck('template_version_id')->unique())->get()->keyBy('id');

        $errors = [];
        $previous = null;
        foreach ($receipts as $index => $receipt) {
            $label = 'Quittung '.$receipt->uuid.' (Nr. '.$receipt->sequence.')';

            if ($receipt->sequence !== $index + 1) {
                $errors[] = $label.': Lücke in der Kette – erwartet Nr. '.($index + 1).'.';
            }
            if ($receipt->prev_hash !== $previous?->content_hash) {
                $errors[] = $label.': Verkettung mit der vorherigen Quittung stimmt nicht.';
            }
            if (! hash_equals((string) $receipt->content_hash, $this->contentHash($receipt))) {
                $errors[] = $label.': Quittungsdaten wurden verändert.';
            }

            $version = $versions->get($receipt->template_version_id);
            if ($version === null || ! hash_equals((string) $receipt->wording_hash, $this->wordingHash($inquiry, $version, $receipt->retention_days))) {
                $errors[] = $label.': Wortlaut der Abfrage stimmt nicht mit der Quittung überein.';
            }
            if ($version === null || ! hash_equals((string) $receipt->form_hash, $this->schema->formHash($version->id, $inquiry->title, $inquiry->intro, $version->fields ?? []))) {
                $errors[] = $label.': Formularprüfsumme stimmt nicht.';
            }

            $response = $receipt->response;
            if ($response === null
                || (int) $response->version !== $receipt->response_version
                || ! hash_equals((string) $receipt->answer_hash, (string) $response->answer_hash)
                || ! hash_equals((string) $receipt->answer_hash, $this->schema->answerHash($response->answers ?? []))) {
                $errors[] = $label.': Antworten wurden verändert.';
            }

            foreach ($receipt->attachments ?? [] as $key => $file) {
                $current = $response ? $this->fileHash($response->answers[$key] ?? null) : null;
                if (($file['sha256'] ?? null) !== null && $current !== null && ! hash_equals($file['sha256'], $current)) {
                    $errors[] = $label.': Hochgeladene Datei „'.$file['name'].'“ wurde verändert.';
                }
            }

            $previous = $receipt;
        }

        return ['valid' => $errors === [], 'count' => $receipts->count(), 'errors' => $errors];
    }

    /** SHA-256 über das kanonische JSON aller Quittungsfelder (inkl. prev_hash). */
    public function contentHash(InquiryReceipt $receipt): string
    {
        return hash('sha256', self::canonicalJson($this->contentPayload($receipt)));
    }

    /**
     * SHA-256 über den vollständigen Wortlaut, den die Eltern gesehen haben: Titel und
     * Einführung der Abfrage, Zweck, Hinweise, zuständige Stelle, Aufbewahrung und alle
     * Felder mit Optionen, Hilfetexten und Einwilligungstexten.
     */
    public function wordingHash(Inquiry $inquiry, InquiryTemplateVersion $version, ?int $retentionDays): string
    {
        return hash('sha256', self::canonicalJson([
            'inquiry' => [
                'title' => $inquiry->title,
                'intro' => $inquiry->intro,
                'retention_days' => $retentionDays,
            ],
            'template' => [
                'id' => (int) $version->id,
                'version' => (int) $version->version,
                'title' => $version->title,
                'purpose' => $version->purpose,
                'parent_info' => $version->parent_info,
                'responsible_office' => $version->responsible_office,
                'retention_days' => $version->retention_days,
                'retention_unlimited' => (bool) $version->retention_unlimited,
                'retention_note' => $version->retention_note,
                'requires_all_guardians' => (bool) $version->requires_all_guardians,
            ],
            'fields' => $version->fields ?? [],
        ]));
    }

    /** Kanonisches JSON: Schlüssel sortiert, Unicode und Schrägstriche unverändert. */
    public static function canonicalJson(array $data): string
    {
        return json_encode(self::sortKeys($data), self::JSON_FLAGS);
    }

    /**
     * @return array<string, mixed>
     */
    private function contentPayload(InquiryReceipt $receipt): array
    {
        // user_id fehlt bewusst: Es wird bei der endgültigen Löschung des Kontos NULL.
        return [
            'uuid' => $receipt->uuid,
            'sequence' => (int) $receipt->sequence,
            'inquiry_id' => (int) $receipt->inquiry_id,
            'assignment_id' => (int) $receipt->assignment_id,
            'response_id' => (int) $receipt->response_id,
            'confirmation_id' => $receipt->confirmation_id !== null ? (int) $receipt->confirmation_id : null,
            'template_version_id' => (int) $receipt->template_version_id,
            'response_version' => (int) $receipt->response_version,
            'kind' => $receipt->kind->value,
            'declarant_name' => $receipt->declarant_name,
            'child_name' => $receipt->child_name,
            'school_name' => $receipt->school_name,
            'channel' => $receipt->channel,
            'declared_at' => $receipt->declared_at->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            'retention_days' => $receipt->retention_days !== null ? (int) $receipt->retention_days : null,
            'attachments' => $receipt->attachments,
            'wording_hash' => $receipt->wording_hash,
            'form_hash' => $receipt->form_hash,
            'answer_hash' => $receipt->answer_hash,
            'prev_hash' => $receipt->prev_hash,
            'layout_version' => (int) $receipt->layout_version,
            'backfilled' => (bool) $receipt->backfilled,
        ];
    }

    /**
     * Daten für die View – ausschließlich aus Quittung, Abfrage, Vorlagenversion und Antwort.
     *
     * @return array<string, mixed>
     */
    private function viewData(InquiryReceipt $receipt): array
    {
        $inquiry = Inquiry::withTrashed()->findOrFail($receipt->inquiry_id);
        $version = InquiryTemplateVersion::findOrFail($receipt->template_version_id);
        $answers = InquiryResponse::findOrFail($receipt->response_id)->answers ?? [];

        $fields = [];
        foreach ($version->fields ?? [] as $field) {
            $type = InquiryFieldType::tryFrom($field['type'] ?? '') ?? InquiryFieldType::Info;
            $isInput = $type->isInput();
            $fields[] = [
                'is_input' => $isInput,
                'label' => (string) ($field['label'] ?? ''),
                'type_label' => $type->label(),
                'required' => $isInput && ! empty($field['required']),
                'help' => $field['help'] ?? null,
                'options' => $type->hasOptions() ? array_values((array) ($field['options'] ?? [])) : [],
                'consent_text' => $type === InquiryFieldType::Consent ? ($field['consent_text'] ?? null) : null,
                'answer' => $isInput ? $this->schema->display($field, $answers[$field['key']] ?? null) : null,
                'file' => $type === InquiryFieldType::File ? ($receipt->attachments[$field['key']] ?? null) : null,
            ];
        }

        $retention = match (true) {
            $receipt->retention_days !== null => 'Die Angaben werden '.$receipt->retention_days.' Tage nach Abschluss der Abfrage gelöscht, soweit keine gesetzliche Aufbewahrungspflicht besteht.',
            default => 'Die Angaben werden aufbewahrt, solange sie für den genannten Zweck benötigt werden, und danach von der Schule gelöscht.',
        };

        return [
            'receipt' => $receipt,
            'inquiry' => $inquiry,
            'version' => $version,
            'fields' => $fields,
            'kindLabel' => $receipt->kind->label(),
            'declaredAt' => $receipt->declared_at->copy()->setTimezone(self::TIMEZONE)->format('d.m.Y, H:i:s'),
            'channelLabel' => match ($receipt->channel) {
                'app' => 'App',
                'mail' => 'E-Mail',
                'paper' => 'Papier (von der Schule erfasst)',
                default => 'Webseite',
            },
            'retentionText' => $retention,
        ];
    }

    private function afterIssue(InquiryReceipt $receipt): void
    {
        try {
            $this->storePdfHash($receipt);
        } catch (Throwable $e) {
            // Die Erklärung ist gespeichert; die Prüfsumme lässt sich jederzeit nachtragen
            report($e);
        }

        event(new ReceiptIssued($receipt));
    }

    /** PDF-Prüfsumme einmalig nachtragen (falls noch nicht vorhanden). */
    public function storePdfHash(InquiryReceipt $receipt): string
    {
        if ($receipt->pdf_sha256 === null) {
            $this->pdf($receipt);
        }

        return $receipt->pdf_sha256;
    }

    /**
     * PDF für Download oder Mailanhang; trägt eine fehlende Prüfsumme dabei nach.
     */
    public function pdf(InquiryReceipt $receipt): string
    {
        $bytes = $this->renderPdf($receipt);
        if ($receipt->pdf_sha256 === null && $receipt->layout_version === self::LAYOUT_VERSION) {
            $receipt->pdf_sha256 = hash('sha256', $bytes);
            $receipt->save();
        }

        return $bytes;
    }

    /**
     * Hochgeladene Dateien als Name, Größe und Prüfsumme (die Datei selbst gehört nicht zur Quittung).
     *
     * @return array<string, array{name: string, size: ?int, sha256: ?string}>|null
     */
    private function attachments(InquiryTemplateVersion $version, array $answers): ?array
    {
        $files = [];
        foreach ($version->fields ?? [] as $field) {
            if (($field['type'] ?? null) !== InquiryFieldType::File->value) {
                continue;
            }
            $value = $answers[$field['key']] ?? null;
            if (is_array($value) && isset($value['name'])) {
                $files[$field['key']] = [
                    'name' => (string) $value['name'],
                    'size' => isset($value['size']) ? (int) $value['size'] : null,
                    'sha256' => $this->fileHash($value),
                ];
            }
        }

        return $files === [] ? null : $files;
    }

    private function fileHash(mixed $value): ?string
    {
        $path = is_array($value) ? ($value['path'] ?? null) : null;
        $disk = Storage::disk(ResponseService::DISK);

        return $path && $disk->exists($path) ? hash('sha256', (string) $disk->get($path)) : null;
    }

    private function schoolName(): string
    {
        $name = rescue(fn () => app(GeneralSetting::class)->app_name, null, false);

        return filled($name) ? (string) $name : (string) config('app.name');
    }

    /** Zeitpunkt so, wie er nach dem Speichern wieder gelesen wird (sekundengenau, App-Zeitzone). */
    private static function storedTime(CarbonInterface $time): CarbonImmutable
    {
        $format = (new InquiryReceipt)->getDateFormat();

        return CarbonImmutable::createFromFormat($format, $time->format($format));
    }

    private static function sortKeys(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return array_map(self::sortKeys(...), $value);
    }
}

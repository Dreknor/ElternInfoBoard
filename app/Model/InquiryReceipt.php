<?php

namespace App\Model;

use App\Enums\InquiryReceiptKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Erklärungsquittung (§ 126b BGB): unveränderlicher Nachweis genau einer Erklärung
 * (Abgabe, Korrektur, übernommene Änderung, Bestätigung, Widerruf) mit Prüfsummen.
 *
 * Nur Einfügen ist erlaubt. Einzige Ausnahme: die abgeleitete PDF-Prüfsumme darf einmalig
 * nachgetragen werden. Gelöscht wird ausschließlich mit der Abfrage (Query bzw. Kaskade).
 *
 * @property InquiryReceiptKind $kind
 * @property array<string, array{name: string, size: ?int, sha256: ?string}>|null $attachments
 */
class InquiryReceipt extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'uuid', 'inquiry_id', 'sequence', 'assignment_id', 'response_id', 'confirmation_id', 'template_version_id',
        'response_version', 'kind', 'user_id', 'declarant_name', 'child_name', 'school_name', 'channel', 'declared_at', 'retention_days',
        'attachments', 'wording_hash', 'form_hash', 'answer_hash', 'prev_hash', 'content_hash', 'pdf_sha256',
        'layout_version', 'backfilled',
    ];

    protected function casts(): array
    {
        return [
            'kind' => InquiryReceiptKind::class,
            'declared_at' => 'datetime',
            'attachments' => 'json:unicode',
            'backfilled' => 'boolean',
            'sequence' => 'integer',
            'response_version' => 'integer',
            'layout_version' => 'integer',
            'retention_days' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (InquiryReceipt $receipt) {
            $dirty = array_keys($receipt->getDirty());
            $pdfHashOnly = $dirty === ['pdf_sha256'] && $receipt->getRawOriginal('pdf_sha256') === null;
            if (! $pdfHashOnly) {
                throw new LogicException('Erklärungsquittungen sind unveränderlich.');
            }
        });

        static::deleting(function () {
            throw new LogicException('Erklärungsquittungen werden nur zusammen mit den Antworten der Abfrage gelöscht.');
        });
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class)->withTrashed();
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(InquiryAssignment::class, 'assignment_id');
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(InquiryResponse::class, 'response_id');
    }

    public function confirmation(): BelongsTo
    {
        return $this->belongsTo(InquiryConfirmation::class, 'confirmation_id');
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(InquiryTemplateVersion::class, 'template_version_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Dateiname für Download und Mailanhang. */
    public function fileName(): string
    {
        return 'Quittung_'.$this->uuid.'.pdf';
    }
}

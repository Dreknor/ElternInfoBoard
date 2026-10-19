<?php

namespace App\Model;

use App\Enums\InquiryReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Unveränderliche Antwortversion (Konzept Kap. 7.2). Korrekturen erzeugen eine neue
 * Version; nur Prüfvermerk und Kennzeichnung der gültigen Version dürfen sich ändern.
 *
 * @property array<string, mixed> $answers
 */
class InquiryResponse extends Model
{
    private const MUTABLE = ['is_current', 'review_status', 'reviewed_by', 'reviewed_at', 'review_note', 'updated_at'];

    protected $fillable = [
        'assignment_id', 'inquiry_id', 'child_id', 'user_id', 'template_version_id', 'version', 'answers',
        'form_hash', 'answer_hash', 'previous_response_id', 'correction_reason', 'is_current', 'channel',
        'ip_anonymized', 'user_agent', 'submitted_at', 'review_status', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'json:unicode',
            'is_current' => 'boolean',
            'submitted_at' => 'datetime',
            'review_status' => InquiryReviewStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (InquiryResponse $response) {
            if (array_diff(array_keys($response->getDirty()), self::MUTABLE) !== []) {
                throw new LogicException('Antwortversionen sind unveränderlich. Korrekturen erzeugen eine neue Version.');
            }
        });
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(InquiryAssignment::class, 'assignment_id');
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(InquiryTemplateVersion::class, 'template_version_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Bestätigungen dieser Version durch weitere Sorgeberechtigte. */
    public function confirmations(): HasMany
    {
        return $this->hasMany(InquiryConfirmation::class, 'response_id')->orderBy('confirmed_at');
    }

    public function previous(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_response_id');
    }
}

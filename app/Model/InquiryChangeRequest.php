<?php

namespace App\Model;

use App\Enums\InquiryChangeRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Änderungsmeldung der Eltern zu einer bereits abgegebenen Antwort (z. B. neue Anschrift).
 * Die Meldung enthält den vollständigen neuen Datensatz; übernimmt das Sekretariat sie,
 * entsteht daraus eine neue Antwortversion (`response_id`). Die Angaben selbst bleiben
 * unverändert – nur Bearbeitungsstand und -vermerk ändern sich.
 *
 * @property array<string, mixed> $answers
 */
class InquiryChangeRequest extends Model
{
    protected $fillable = [
        'inquiry_id', 'assignment_id', 'child_id', 'user_id', 'base_response_id', 'answers', 'message', 'status',
        'channel', 'ip_anonymized', 'user_agent', 'handled_by', 'handled_at', 'handled_note', 'response_id',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'json:unicode',
            'status' => InquiryChangeRequestStatus::class,
            'handled_at' => 'datetime',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(InquiryAssignment::class, 'assignment_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function baseResponse(): BelongsTo
    {
        return $this->belongsTo(InquiryResponse::class, 'base_response_id');
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(InquiryResponse::class, 'response_id');
    }

    public function isPending(): bool
    {
        return $this->status === InquiryChangeRequestStatus::Pending;
    }

    public function scopePending($query)
    {
        return $query->where('status', InquiryChangeRequestStatus::Pending->value);
    }
}

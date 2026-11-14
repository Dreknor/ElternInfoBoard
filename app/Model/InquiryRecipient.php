<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Berechtigtes Elternkonto eines Abfrage-Auftrags inkl. Zustellstatus.
 */
class InquiryRecipient extends Model
{
    protected $fillable = ['assignment_id', 'user_id', 'notified_at', 'delivery_failed_at', 'delivery_error'];

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
            'delivery_failed_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(InquiryAssignment::class, 'assignment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

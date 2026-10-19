<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bestätigung einer Antwortversion durch eine weitere sorgeberechtigte Person.
 * Gilt nur für genau diese Version – eine Korrektur erfordert neue Bestätigungen.
 */
class InquiryConfirmation extends Model
{
    protected $fillable = [
        'response_id', 'assignment_id', 'user_id', 'channel', 'ip_anonymized', 'user_agent', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
        ];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(InquiryResponse::class, 'response_id');
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

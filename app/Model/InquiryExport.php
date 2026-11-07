<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Protokollierter Export mit Ablaufdatum (Konzept Kap. 9).
 */
class InquiryExport extends Model
{
    protected $fillable = [
        'inquiry_id', 'created_by', 'group_id', 'fields', 'format', 'target', 'record_count', 'missing_count',
        'path', 'expires_at', 'download_count', 'last_downloaded_at', 'file_deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'expires_at' => 'datetime',
            'last_downloaded_at' => 'datetime',
            'file_deleted_at' => 'datetime',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isAvailable(): bool
    {
        return $this->path !== null && $this->file_deleted_at === null && $this->expires_at->isFuture();
    }
}

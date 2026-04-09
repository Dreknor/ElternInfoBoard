<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ReminderLog extends Model
{
    public $timestamps = false;

    protected $table = 'reminder_logs';

    protected $fillable = [
        'remindable_type',
        'remindable_id',
        'user_id',
        'post_id',
        'level',
        'channel',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'level' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────

    public function remindable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeForPost($query, int $postId)
    {
        return $query->where('post_id', $postId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    public function scopeForRemindable($query, string $type, int $id)
    {
        return $query->where('remindable_type', $type)->where('remindable_id', $id);
    }
}


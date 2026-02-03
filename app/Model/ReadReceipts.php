<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadReceipts extends Model
{
    use HasFactory;

    protected $fillable = ['post_id', 'user_id', 'reminded_at', 'final_reminder_sent_at', 'confirmed_at'];

    protected function casts(): array
    {
        return [
            'reminded_at' => 'datetime',
            'final_reminder_sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

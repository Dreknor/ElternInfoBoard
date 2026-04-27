<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Message extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'type',
        'reply_to_id',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    // ── Beziehungen ───────────────────────────────────────────────

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id')->withTrashed();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(MessageReport::class);
    }

    // ── Medien-Anhänge ────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('message-attachments')->singleFile();
    }

    // ── Hilfsmethoden ────────────────────────────────────────────

    public function isEditableBy(User $user): bool
    {
        if ($this->sender_id !== $user->id) {
            return false;
        }

        return $this->created_at->diffInMinutes(now()) <= 15;
    }

    public function isDeletableBy(User $user): bool
    {
        return $this->sender_id === $user->id
            || $user->can('moderate messages');
    }

    /**
     * Prüft, ob diese Nachricht für den User sichtbar ist
     * (er muss Mitglied sein UND seit vor der Nachricht beigetreten sein).
     */
    public function isVisibleTo(User $user): bool
    {
        $pivot = \Illuminate\Support\Facades\DB::table('conversation_user')
            ->where('conversation_id', $this->conversation_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $pivot) {
            return false;
        }

        if ($pivot->joined_at && $this->created_at < \Carbon\Carbon::parse($pivot->joined_at)) {
            return false;
        }

        return true;
    }
}


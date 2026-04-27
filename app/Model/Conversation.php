<?php

namespace App\Model;

use App\Traits\NotificationTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory;
    use NotificationTrait;
    use SoftDeletes;

    protected $fillable = [
        'type',
        'group_id',
        'title',
        'created_by',
        'is_active',
        'auto_delete_days',
    ];

    protected function casts(): array
    {
        return [
            'is_active'        => 'boolean',
            'auto_delete_days' => 'integer',
        ];
    }

    // ── Beziehungen ───────────────────────────────────────────────

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class)->withoutGlobalScopes();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withPivot(['joined_at', 'muted_until', 'last_read_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    // ── Scopes ────────────────────────────────────────────────────

    /**
     * Nur Konversationen des angegebenen Users zurückgeben.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('users', fn ($q) => $q->where('users.id', $userId));
    }

    // ── Hilfsmethoden ────────────────────────────────────────────

    /**
     * Gibt die Anzahl ungelesener Nachrichten für einen User zurück.
     * Berücksichtigt nur Nachrichten, die NACH dem Beitritt des Users entstanden sind.
     */
    public function unreadCountFor(int $userId): int
    {
        $pivot = $this->users->where('id', $userId)->first()?->pivot;
        if (! $pivot) {
            return 0;
        }

        $query = $this->messagesVisibleTo($userId)->where('sender_id', '!=', $userId);
        if ($pivot->last_read_at) {
            $query->where('messages.created_at', '>', $pivot->last_read_at);
        }

        return $query->count();
    }

    /**
     * Ermittelt den Anzeigenamen der Konversation aus User-Perspektive (für 1:1-Chats).
     */
    public function displayNameFor(int $userId): string
    {
        if ($this->title) {
            return $this->title;
        }

        if ($this->type === 'direct') {
            $other = $this->users->where('id', '!=', $userId)->first();
            return $other?->name ?? 'Unbekannt';
        }

        return $this->group?->name ?? 'Gruppenkonversation';
    }
}


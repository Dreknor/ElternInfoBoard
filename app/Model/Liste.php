<?php

namespace App\Model;

use App\Observers\ListeObserver;
use App\Traits\NotificationTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

#[ObservedBy([ListeObserver::class])]
class Liste extends Model
{
    use HasFactory;
    use HasRelationships;
    use NotificationTrait;

    /** Begrenzung „einmal buchbar“ je Familie (bisheriges Verhalten). */
    public const BOOKING_FAMILY = 'family';

    /** Begrenzung je Kind: jede Buchung/Eintragung gehört zu einem Kind („1 Helfer je Kind“). */
    public const BOOKING_CHILD = 'child';

    protected $table = 'listen';

    protected $fillable = ['listenname', 'type', 'comment', 'besitzer', 'visible_for_all', 'active', 'ende', 'duration', 'multiple', 'booking_scope', 'make_new_entry', 'creates_pflichtstunden'];

    protected $visible = ['id', 'listenname', 'type', 'comment', 'besitzer', 'visible_for_all', 'active', 'ende', 'duration', 'multiple', 'booking_scope', 'make_new_entry', 'creates_pflichtstunden'];

    protected function casts(): array
    {
        return [
            'ende' => 'datetime',
            'visible_for_all' => 'boolean',
            'active' => 'boolean',
            'multiple' => 'boolean',
            'make_new_entry' => 'boolean',
            'creates_pflichtstunden' => 'boolean',
        ];
    }

    public function bookingPerChild(): bool
    {
        return $this->booking_scope === self::BOOKING_CHILD;
    }

    public function ersteller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'besitzer')->withDefault([
            'name' => config('app.name'),
        ]);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_listen', 'liste_id');
    }

    public function eintragungen(): HasMany
    {
        return $this->hasMany(Listen_Eintragungen::class, 'listen_id');
    }

    public function termine(): HasMany
    {
        return $this->hasMany(listen_termine::class, 'listen_id');
    }

    /**
     * Empfänger über die Gruppen. Für alle Empfänger (inkl. direkt
     * freigegebener Personen) recipients() bzw. hasRecipient() verwenden.
     */
    public function users(): HasManyDeep
    {
        return $this->hasManyDeep(User::class, ['group_listen', Group::class, 'group_user']);
    }

    /**
     * Direkt (ohne Gruppe) freigegebene Personen.
     */
    public function recipientUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'liste_user')->withTimestamps();
    }

    /**
     * Alle Empfänger: Mitglieder der Gruppen und direkt freigegebene Personen, ohne Duplikate.
     */
    public function recipients(): EloquentCollection
    {
        return $this->users->concat($this->recipientUsers)->unique('id')->values();
    }

    public function hasRecipient(User $user): bool
    {
        return self::query()->whereKey($this->id)->forRecipient($user)->exists();
    }

    /**
     * Schränkt eine Abfrage auf Listen ein, die der Nutzer über eine Gruppe
     * oder als direkt freigegebene Person erhält (Tabelle `listen`).
     */
    public static function constrainToRecipient($query, User|int $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;

        $query->where(function ($q) use ($userId) {
            $q->whereExists(function ($sub) use ($userId) {
                $sub->selectRaw(1)
                    ->from('group_listen')
                    ->join('group_user', 'group_listen.group_id', '=', 'group_user.group_id')
                    ->whereColumn('group_listen.liste_id', 'listen.id')
                    ->where('group_user.user_id', $userId);
            })->orWhereExists(function ($sub) use ($userId) {
                $sub->selectRaw(1)
                    ->from('liste_user')
                    ->whereColumn('liste_user.liste_id', 'listen.id')
                    ->where('liste_user.user_id', $userId);
            });
        });
    }

    public function scopeForRecipient(Builder $query, User|int $user): Builder
    {
        self::constrainToRecipient($query, $user);

        return $query;
    }
}

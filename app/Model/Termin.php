<?php

namespace App\Model;

use App\Traits\NotificationTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\CalendarLinks\Link;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Termin extends Model implements Auditable
{
    use HasFactory;
    use HasRelationships;
    use NotificationTrait;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'termine';

    /**
     * Gesetzt bei nicht gespeicherten Einträgen aus anderen Modulen (z. B.
     * Reinigungsdienst, siehe Reinigung::toTermin()): Ziel-Link statt Bearbeiten.
     */
    public ?string $sourceUrl = null;

    protected $fillable = ['start', 'ende', 'terminname', 'fullDay', 'public'];

    protected $visible = ['start', 'ende', 'terminname', 'fullDay', 'public', 'id'];

    protected function casts(): array
    {
        return [
            'creted_at' => 'datetime',
            'start' => 'datetime',
            'ende' => 'datetime',
            'fullDay' => 'boolean',
            'public' => 'boolean',
        ];
    }

    public function getfullDayAttribute($value): bool
    {
        // Vorher `$value = false` (Zuweisung) – dadurch galt jeder Termin mit gespeichertem Wert als ganztägig.
        return (bool) $value;
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_termine');
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('date', function (Builder $builder) {
            $builder->whereDate('start', '>=', Carbon::yesterday()->startOfDay())
                ->orWhereDate('ende', '>=', Carbon::tomorrow()->startOfDay());
        });

        static::created(function () {
            return Cache::forget('termine'.auth()->id());
        });
    }

    public function link($prefix = null): Link
    {
        if (! is_null($prefix)) {
            $terminname = '('.$prefix.') '.$this->terminname;
        } else {
            $terminname = $this->terminname;
        }

        if ($this->fullDay == 1) {
            $ende = $this->ende->addDay();

            return Link::create($terminname, $this->start, $ende, $this->fullDay);
        }

        return Link::create($terminname, $this->start, $this->ende, $this->fullDay);
    }

    /**
     * Empfänger über die Gruppen. Für alle Empfänger (inkl. direkt
     * freigegebener Personen) recipients() verwenden.
     */
    public function users(): HasManyDeep
    {
        return $this->hasManyDeep(User::class, ['group_termine', Group::class, 'group_user']);
    }

    /**
     * Direkt (ohne Gruppe) freigegebene Personen.
     */
    public function recipientUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'termin_user')->withTimestamps();
    }

    /**
     * Alle Empfänger: Mitglieder der Gruppen und direkt freigegebene Personen, ohne Duplikate.
     */
    public function recipients(): EloquentCollection
    {
        return $this->users->concat($this->recipientUsers)->unique('id')->values();
    }

    /**
     * Schränkt eine Abfrage auf Termine ein, die der Nutzer über eine Gruppe
     * oder als direkt freigegebene Person erhält (Tabelle `termine`).
     */
    public static function constrainToRecipient($query, User|int $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;

        $query->where(function ($q) use ($userId) {
            $q->whereExists(function ($sub) use ($userId) {
                $sub->selectRaw(1)
                    ->from('group_termine')
                    ->join('group_user', 'group_termine.group_id', '=', 'group_user.group_id')
                    ->whereColumn('group_termine.termin_id', 'termine.id')
                    ->where('group_user.user_id', $userId);
            })->orWhereExists(function ($sub) use ($userId) {
                $sub->selectRaw(1)
                    ->from('termin_user')
                    ->whereColumn('termin_user.termin_id', 'termine.id')
                    ->where('termin_user.user_id', $userId);
            });
        });
    }

    public function scopeForRecipient(Builder $query, User|int $user): Builder
    {
        self::constrainToRecipient($query, $user);

        return $query;
    }
}

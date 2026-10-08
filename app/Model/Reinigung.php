<?php

namespace App\Model;

use App\Services\App\Modules;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reinigung extends Model
{
    use HasFactory;

    /**
     * Pseudo-Bereich, der verwendet wird, wenn der Reinigungsplan gemäß
     * ReinigungSetting::$separate_bereiche als gemeinsamer Plan für die gesamte
     * Einrichtung geführt wird (oder wenn bei den Gruppen keine Bereiche gepflegt sind).
     */
    public const BEREICH_GESAMT = 'Gesamt';

    protected $table = 'reinigung';

    protected $visible = ['bereich', 'aufgabe', 'datum', 'bemerkung'];

    protected $fillable = ['bereich', 'aufgabe', 'datum', 'bemerkung', 'users_id'];

    public function getDatumAttribute($value): bool|Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id', 'id');
    }

    /**
     * Anstehende Reinigungsdienste der Familie des Nutzers (FamilyResolver), sofern
     * das Modul "Reinigung" für den Nutzer aktiv ist. Einsätze laufen jeweils über die
     * ganze Woche; ein Einsatz gilt bis zum Sonntag seiner Woche als anstehend.
     * Mit $from kann ein früherer Beginn (z. B. für Kalenderansichten) gewählt werden.
     *
     * @return Collection<int, self>
     */
    public static function upcomingForFamily(User $user, ?Carbon $until = null, ?Carbon $from = null): Collection
    {
        if (! Modules::isActiveFor($user, 'Reinigung')) {
            return new Collection;
        }

        return self::query()
            ->whereIn('users_id', $user->familyUserIds())
            ->whereDate('datum', '>=', ($from ?? Carbon::now())->copy()->startOfWeek()->toDateString())
            ->when($until, fn ($query) => $query->whereDate('datum', '<=', $until->toDateString()))
            ->orderBy('datum')
            ->get();
    }

    /**
     * Bemerkung als Liste einzelner (abhakbarer) Punkte: je Zeile bzw. durch Semikolon getrennt.
     *
     * @return string[]
     */
    public function bemerkungPunkte(): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R|;/u', (string) $this->bemerkung)),
            fn ($punkt) => $punkt !== ''
        ));
    }

    public function weekStart(): Carbon
    {
        return $this->datum->copy()->startOfWeek()->startOfDay();
    }

    public function weekEnd(): Carbon
    {
        return $this->datum->copy()->endOfWeek()->startOfDay();
    }

    public function terminTitle(): string
    {
        return 'Reinigungsdienst'.($this->aufgabe ? ': '.$this->aufgabe : '');
    }

    /**
     * Nicht gespeicherter, ganztägiger Termin über die Einsatzwoche - für die
     * Anzeige in Terminlisten (Dashboard, Termine-Übersicht).
     */
    public function toTermin(): Termin
    {
        $termin = new Termin([
            'terminname' => $this->terminTitle(),
            'start' => $this->weekStart(),
            'ende' => $this->weekEnd(),
            'fullDay' => true,
        ]);
        $termin->sourceUrl = url('reinigung');

        return $termin;
    }
}

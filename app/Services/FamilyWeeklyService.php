<?php

namespace App\Services;

use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\Group;
use App\Model\Holiday;
use App\Model\Krankmeldungen;
use App\Model\Schickzeiten;
use App\Model\Termin;
use App\Model\User;
use App\Model\Vertretung;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class FamilyWeeklyService
{
    public function __construct(
        private StundenplanDataProvider $stundenplanProvider,
    ) {}

    /**
     * Hauptmethode: Holt alle Wochendaten für alle Kinder eines Users.
     *
     * @return array{
     *   children: Collection,
     *   holidays: Collection,
     *   week_start: Carbon,
     *   week_end: Carbon,
     *   week_label: string,
     * }
     */
    public function getWeeklyData(User $user, ?Carbon $weekStart = null): array
    {
        $weekStart = ($weekStart ?? now())->copy()->startOfWeek();
        $weekEnd   = $weekStart->copy()->addDays(4); // Freitag

        $children = $user->children();

        if (is_null($children) || $children->isEmpty()) {
            return $this->emptyResult($weekStart, $weekEnd);
        }

        // Pivot-Relation von jedem Kind entfernen, bevor es in den Cache geht.
        // Der BelongsToMany-Pivot speichert eine Rückwärtsreferenz auf den User-Parent,
        // was beim FileCache-Serialize zu Problemen führt (insb. mit Sanctum-Mock-Tokens in Tests).
        $children->each(fn (Child $child) => $child->setRelations([]));

        $cacheKey = "family_weekly_{$user->id}_{$weekStart->format('Y-W')}";

        return Cache::remember($cacheKey, 300, function () use ($children, $weekStart, $weekEnd) {
            $holidays       = Holiday::betweenDates($weekStart, $weekEnd)->get();
            $stundenplanData = $this->stundenplanProvider->getStundenplanData();

            $childrenData = $children->unique('id')->map(
                fn (Child $child) => $this->buildChildWeeklyData($child, $weekStart, $weekEnd, $stundenplanData, $holidays)
            )->values();

            return [
                'children'   => $childrenData,
                'holidays'   => $holidays,
                'week_start' => $weekStart,
                'week_end'   => $weekEnd,
                'week_label' => 'KW ' . $weekStart->isoWeek() . ': ' .
                                $weekStart->format('d.') . '–' . $weekEnd->format('d. F Y'),
            ];
        });
    }

    /**
     * Baut das Wochendaten-Array für ein einzelnes Kind.
     */
    private function buildChildWeeklyData(
        Child $child,
        Carbon $weekStart,
        Carbon $weekEnd,
        ?array $stundenplanData,
        Collection $holidays,
    ): array {
        $klasseKurzform = $this->getKlasseKurzform($child);

        $days = [];
        for ($i = 0; $i < 5; $i++) {
            $date      = $weekStart->copy()->addDays($i);
            $dayOfWeek = $i + 1; // 1=Mo … 5=Fr

            $holiday = $holidays->first(fn ($h) => $h->includesDate($date));

            $days[$dayOfWeek] = [
                'date'         => $date,
                'is_holiday'   => ! is_null($holiday),
                'holiday_name' => $holiday?->name,
                'stundenplan'  => $this->getStundenplanForDay($stundenplanData, $klasseKurzform, $dayOfWeek),
                'vertretungen' => $this->getVertretungenForDay($klasseKurzform, $date),
                'gtas'         => $this->getGTAsForDay($child, $dayOfWeek, $date),
                'schickzeiten' => $this->getSchickzeitenForDay($child, $date),
                'krankmeldung' => $this->getKrankmeldungForDay($child, $date),
                'checkIn'      => $this->getCheckInForDay($child, $date),
            ];
        }

        $termine = $this->getTermineForWeek($child, $weekStart, $weekEnd);

        return [
            'child'   => $child,
            'klasse'  => $klasseKurzform,
            'days'    => $days,
            'termine' => $termine,
            'summary' => $this->buildWeekSummary($days, $termine),
        ];
    }

    /**
     * Ermittelt die Klassen-Kurzform (für Stundenplan-Lookup).
     */
    private function getKlasseKurzform(Child $child): ?string
    {
        $groupId = $child->class_id ?? $child->group_id;
        if (! $groupId) {
            return null;
        }

        $group = Group::withoutGlobalScopes()->find($groupId);

        return $group?->name;
    }

    /**
     * Stundenplan für einen bestimmten Wochentag aus den vorhandenen Daten extrahieren.
     */
    private function getStundenplanForDay(?array $data, ?string $klasse, int $dayOfWeek): array
    {
        if (! $data || ! $klasse) {
            return [];
        }

        $entries = [];
        foreach ($data['Klassen'] ?? [] as $klasseData) {
            if (($klasseData['Kurzform'] ?? '') !== $klasse) {
                continue;
            }

            foreach ($klasseData['Plan'] ?? [] as $entry) {
                if ((string) ($entry['PlTg'] ?? null) == (string) $dayOfWeek) {
                    $stunde             = (int) $entry['PlSt'];
                    $entries[$stunde] = [
                        'stunde' => $stunde,
                        'fach'   => $entry['PlFa'] ?? '—',
                        'lehrer' => $entry['PlLe'] ?? [],
                        'raum'   => $entry['PlRa'] ?? [],
                    ];
                }
            }
            break;
        }

        ksort($entries);

        return $entries;
    }

    /**
     * Vertretungen für einen Tag und eine Klasse laden.
     */
    private function getVertretungenForDay(?string $klasse, Carbon $date): Collection
    {
        if (! $klasse) {
            return collect();
        }

        return Vertretung::withoutGlobalScope('date')
            ->where('klasse_kurzform', $klasse)
            ->whereDate('date', $date)
            ->get();
    }

    /**
     * GTAs, die an diesem Wochentag stattfinden.
     */
    private function getGTAsForDay(Child $child, int $dayOfWeek, Carbon $date): Collection
    {
        // Carbon dayOfWeek: 0=So, 1=Mo … 6=Sa
        // Wir übergeben 1-basiert (Mo=1), AG-Modell speichert dayOfWeek wie Carbon
        $carbonDow = $dayOfWeek % 7; // Mo=1→1 … Fr=5→5 (So=0 bleibt 0)

        return $child->arbeitsgemeinschaften()
            ->where('weekday', $carbonDow)
            ->where(fn ($q) => $q->whereDate('start_date', '<=', $date)->orWhereNull('start_date'))
            ->where(fn ($q) => $q->whereDate('end_date', '>=', $date)->orWhereNull('end_date'))
            ->get();
    }

    /**
     * Schickzeiten für einen bestimmten Tag (nur specific_date).
     */
    private function getSchickzeitenForDay(Child $child, Carbon $date): Collection
    {
        return Schickzeiten::where('child_id', $child->id)
            ->whereDate('specific_date', $date->format('Y-m-d'))
            ->get();
    }

    /**
     * Aktive Krankmeldung für einen Tag.
     */
    private function getKrankmeldungForDay(Child $child, Carbon $date): ?Krankmeldungen
    {
        return Krankmeldungen::where('child_id', $child->id)
            ->whereDate('start', '<=', $date)
            ->whereDate('ende', '>=', $date)
            ->first();
    }

    /**
     * CheckIn-Status für einen Tag.
     */
    private function getCheckInForDay(Child $child, Carbon $date): ?ChildCheckIn
    {
        return ChildCheckIn::where('child_id', $child->id)
            ->whereDate('date', $date)
            ->first();
    }

    /**
     * Termine der Woche (über die Klassen-Gruppe des Kindes).
     */
    private function getTermineForWeek(Child $child, Carbon $start, Carbon $end): Collection
    {
        $groupId = $child->class_id ?? $child->group_id;
        if (! $groupId) {
            return collect();
        }

        return Termin::withoutGlobalScope('date')
            ->whereHas('groups', fn ($q) => $q->withoutGlobalScopes()->where('groups.id', $groupId))
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start', [$start->startOfDay(), $end->endOfDay()])
                  ->orWhereBetween('ende', [$start->startOfDay(), $end->endOfDay()]);
            })
            ->orderBy('start')
            ->get();
    }

    /**
     * Wochen-Zusammenfassung für ein Kind.
     */
    private function buildWeekSummary(array $days, Collection $termine): array
    {
        $holidayDays     = collect($days)->where('is_holiday', true)->count();
        $sickDays        = collect($days)->filter(fn ($d) => $d['krankmeldung'] !== null)->count();
        $hasVertretungen = collect($days)->contains(fn ($d) => $d['vertretungen']->isNotEmpty());
        $pendingAbfragen = collect($days)->filter(
            fn ($d) => $d['checkIn'] !== null
                       && $d['checkIn']->lock_at !== null
                       && $d['checkIn']->should_be === null
        )->count();

        return [
            'holiday_days'     => $holidayDays,
            'sick_days'        => $sickDays,
            'has_vertretungen' => $hasVertretungen,
            'termine_count'    => $termine->count(),
            'school_days'      => 5 - $holidayDays,
            'pending_abfragen' => $pendingAbfragen,
        ];
    }

    /**
     * Leeres Ergebnis, wenn kein Kind verknüpft ist.
     */
    private function emptyResult(Carbon $weekStart, Carbon $weekEnd): array
    {
        return [
            'children'   => collect(),
            'holidays'   => collect(),
            'week_start' => $weekStart,
            'week_end'   => $weekEnd,
            'week_label' => 'KW ' . $weekStart->isoWeek() . ': ' .
                            $weekStart->format('d.') . '–' . $weekEnd->format('d. F Y'),
        ];
    }
}


<?php

namespace App\Services\Pflichtstunden;

use App\Enums\GuardianRight;
use App\Model\Child;
use App\Model\Pflichtstunde;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use App\Services\Family\FamilyUnit;
use App\Settings\PflichtstundenSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Zentrale Pflichtstunden-Berechnung (Web-Übersicht, Verwaltung, API, Export).
 *
 * Einheiten entstehen aus den Familien des FamilyResolvers; Soll-Stunden
 * richten sich nach den Settings (Basis Familie/Kind, Umgang mit geteilten
 * Kindern, Deckelung, Gruppenfilter).
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §6.2
 */
class PflichtstundenService
{
    public const BASIS_FAMILY = 'family';

    public const BASIS_CHILD = 'child';

    public const SHARED_COMBINED = 'combined';

    public const SHARED_SPLIT = 'split';

    public const SHARED_SEPARATE = 'separate';

    public function __construct(
        private readonly FamilyResolver $resolver,
        private readonly PflichtstundenSetting $settings,
    ) {}

    // ── Zeitraum ─────────────────────────────────────────────────────────────

    /**
     * Aktueller Abrechnungszeitraum (wie Global Scope „aktuellerZeitraum“).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function currentPeriod(): array
    {
        $start = Carbon::createFromFormat('m-d', $this->settings->pflichtstunden_start)->startOfDay();
        if ($start->isFuture()) {
            $start->subYear();
        }
        $end = Carbon::createFromFormat('m-d', $this->settings->pflichtstunden_ende)->endOfDay();
        if ($end->isPast()) {
            $end->addYear();
        }

        return [$start, $end];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodForYear(?int $year): array
    {
        if ($year === null) {
            return $this->currentPeriod();
        }

        return [
            Carbon::createFromFormat('Y-m-d', $year.'-'.$this->settings->pflichtstunden_start)->startOfDay(),
            Carbon::createFromFormat('Y-m-d', ($year + 1).'-'.$this->settings->pflichtstunden_ende)->endOfDay(),
        ];
    }

    // ── Einheiten ────────────────────────────────────────────────────────────

    /**
     * Alle Personen, die Pflichtstunden leisten (Permission „view Pflichtstunden“).
     *
     * @return Collection<int, User>
     */
    public function participants(): Collection
    {
        try {
            return User::permission('view Pflichtstunden')->get();
        } catch (PermissionDoesNotExist) {
            return collect();
        }
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $period
     * @param  Collection<int, User>|null  $participants
     * @return Collection<int, PflichtstundenUnit>
     */
    public function units(?array $period = null, ?Collection $participants = null): Collection
    {
        [$start, $end] = $period ?? $this->currentPeriod();
        $users = ($participants ?? $this->participants())->unique('id')->values();

        if ($users->isEmpty()) {
            return collect();
        }

        $families = $this->resolver->familyUnits($users)->values();

        // Kinder der Familien: Beziehungen mit Sorgerecht, gefiltert auf zählende Kinder
        $childIdsByUser = $this->resolver->childIdsByUser($users, GuardianRight::Custody);
        $countable = $this->countableChildIds(collect($childIdsByUser)->flatten()->unique()->all());

        $familyChildren = [];
        $familiesOfChild = [];
        foreach ($families as $family) {
            $ids = [];
            foreach ($family->userIds as $userId) {
                array_push($ids, ...($childIdsByUser[$userId] ?? []));
            }
            $ids = array_values(array_intersect(array_unique($ids), $countable));
            $familyChildren[$family->key] = $ids;
            foreach ($ids as $childId) {
                $familiesOfChild[$childId][$family->key] = true;
            }
        }

        $groups = $this->mode() === self::SHARED_COMBINED
            ? $this->combineFamilies($families, $familyChildren, $familiesOfChild)
            : $families->map(fn (FamilyUnit $family) => [$family])->all();

        $minutesByUser = Pflichtstunde::withoutGlobalScope('aktuellerZeitraum')
            ->where('approved', true)
            ->whereIn('user_id', $users->modelKeys())
            ->whereBetween('start', [$start, $end])
            ->get(['id', 'user_id', 'start', 'end'])
            ->groupBy('user_id')
            ->map(fn (Collection $entries) => (int) $entries->sum('duration'));

        $usersById = $users->keyBy('id');

        return collect($groups)->map(function (array $group) use ($familyChildren, $familiesOfChild, $minutesByUser, $usersById) {
            $userIds = collect($group)->flatMap(fn (FamilyUnit $f) => $f->userIds)->unique()->sort()->values()->all();
            $childIds = collect($group)->flatMap(fn (FamilyUnit $f) => $familyChildren[$f->key])->unique()->values()->all();
            [$required, $share] = $this->requiredMinutes($group, $childIds, $familyChildren, $familiesOfChild);

            return new PflichtstundenUnit(
                key: collect($group)->pluck('key')->implode('+'),
                label: collect($group)->pluck('label')->unique()->implode(' / '),
                userIds: $userIds,
                members: collect($userIds)->map(fn ($id) => $usersById->get($id))->filter()->values(),
                familyKeys: collect($group)->pluck('key')->values()->all(),
                childIds: $childIds,
                childShare: $share,
                requiredMinutes: $required,
                doneMinutes: (int) collect($userIds)->sum(fn ($id) => $minutesByUser->get($id, 0)),
                hourlyRate: (float) $this->settings->pflichtstunden_betrag,
            );
        })->values();
    }

    /**
     * Einheit des Users. Ist der User selbst kein Teilnehmer, wird er mit seiner
     * Familie ergänzt.
     *
     * @param  Collection<int, PflichtstundenUnit>|null  $units
     */
    public function unitFor(User $user, ?Collection $units = null): PflichtstundenUnit
    {
        $unit = ($units ?? $this->units())->first(fn (PflichtstundenUnit $u) => $u->contains($user->id));

        if ($unit !== null) {
            return $unit;
        }

        $participants = $this->participants()
            ->merge(User::query()->whereIn('id', $this->resolver->familyUserIds($user))->get());

        return $this->units(null, $participants)->first(fn (PflichtstundenUnit $u) => $u->contains($user->id));
    }

    /**
     * Pflichtstunden-Einträge der eigenen Familie im aktuellen Zeitraum (alle Status).
     * Bewusst nur die Familie, nicht eine per „combined“ verbundene Einheit.
     *
     * @return Collection<int, Pflichtstunde>
     */
    public function entriesFor(User $user): Collection
    {
        return Pflichtstunde::query()
            ->whereIn('user_id', $this->resolver->familyUserIds($user))
            ->with('user')
            ->orderByDesc('start')
            ->get();
    }

    // ── Auswertungen ─────────────────────────────────────────────────────────

    /**
     * Gamification/Statistik für einen User (Web-Übersicht und API).
     */
    public function ranking(User $user): array
    {
        $units = $this->units();
        $unit = $this->unitFor($user, $units);
        $progress = $unit->percent();

        return [
            'unit' => $unit,
            'total_parents' => max(1, $units->count()),
            'your_rank' => max(1, $units->filter(fn (PflichtstundenUnit $u) => $u->percent() >= $progress)->count()),
            'avg_progress' => round((float) ($units->avg(fn (PflichtstundenUnit $u) => $u->percent()) ?? $progress), 2),
            'your_progress' => $progress,
            'total_minutes_completed' => $unit->doneMinutes,
            'total_hours_completed' => round($unit->doneMinutes / 60, 2),
            'required_minutes' => $unit->requiredMinutes,
            'required_hours' => round($unit->requiredMinutes / 60, 2),
            'open_minutes' => $unit->openMinutes(),
            'open_hours' => round($unit->openMinutes() / 60, 2),
            'remaining_payment' => $unit->beitrag(),
        ];
    }

    /**
     * Verwaltungsübersicht (Tabelle + Kennzahlen) für einen Zeitraum.
     *
     * @param  array{0: Carbon, 1: Carbon}|null  $period
     * @return array{rows: Collection<int, array>, stats: array}
     */
    public function overview(?array $period = null): array
    {
        $units = $this->units($period);

        $rows = $units->map(fn (PflichtstundenUnit $unit) => [
            'unit' => $unit,
            'label' => $unit->label,
            'user' => $unit->primaryMember(),
            'partner' => $unit->otherMembers()->first(),
            'members' => $unit->members,
            'totalMinutes' => $unit->doneMinutes,
            'requiredMinutes' => $unit->requiredMinutes,
            'openMinutes' => $unit->openMinutes(),
            'beitrag' => $unit->beitrag(),
            'percent' => $unit->percent(),
        ])->values();

        $stats = [
            'totalFamilies' => $units->count(),
            'completed' => $units->filter(fn (PflichtstundenUnit $u) => $u->percent() >= 100)->count(),
            'partial' => $units->filter(fn (PflichtstundenUnit $u) => $u->percent() > 0 && $u->percent() < 100)->count(),
            'notStarted' => $units->filter(fn (PflichtstundenUnit $u) => $u->percent() <= 0)->count(),
            'totalHoursCompleted' => $units->sum(fn (PflichtstundenUnit $u) => $u->doneMinutes) / 60,
            'totalHoursMissing' => $units->sum(fn (PflichtstundenUnit $u) => $u->openMinutes()) / 60,
            'totalHoursRequired' => $units->sum(fn (PflichtstundenUnit $u) => $u->requiredMinutes) / 60,
            'totalBeitrag' => $units->sum(fn (PflichtstundenUnit $u) => $u->beitrag()),
            'avgPercent' => $units->isNotEmpty() ? round($units->avg(fn (PflichtstundenUnit $u) => $u->percent()), 2) : 0,
        ];

        return ['rows' => $rows, 'stats' => $stats];
    }

    /**
     * Kurzbeschreibung der Berechnungsgrundlage für Anzeigen (Eltern, Export).
     */
    public function basisDescription(): string
    {
        $hours = $this->settings->pflichtstunden_anzahl;
        $text = $this->basis() === self::BASIS_CHILD
            ? "{$hours} h pro Kind"
            : "{$hours} h pro Familie";

        if ($this->basis() === self::BASIS_CHILD && $this->settings->pflichtstunden_max_kinder) {
            $text .= " (höchstens {$this->settings->pflichtstunden_max_kinder} Kinder)";
        }

        $text .= match ($this->mode()) {
            self::SHARED_COMBINED => ', Familien mit gemeinsamem Kind gemeinsam',
            self::SHARED_SPLIT => ', gemeinsame Kinder anteilig',
            default => '',
        };

        return $text;
    }

    public function basis(): string
    {
        return $this->settings->pflichtstunden_basis === self::BASIS_CHILD ? self::BASIS_CHILD : self::BASIS_FAMILY;
    }

    public function mode(): string
    {
        return in_array($this->settings->pflichtstunden_geteilte_kinder, [self::SHARED_COMBINED, self::SHARED_SPLIT], true)
            ? $this->settings->pflichtstunden_geteilte_kinder
            : self::SHARED_SEPARATE;
    }

    // ── intern ───────────────────────────────────────────────────────────────

    /**
     * Zählende Kinder: aktiv (Klasse oder Gruppe gesetzt) und ggf. in den
     * konfigurierten Gruppen.
     *
     * @param  list<int>  $childIds
     * @return list<int>
     */
    private function countableChildIds(array $childIds): array
    {
        if ($childIds === []) {
            return [];
        }

        $groups = array_values(array_filter(array_map('intval', $this->settings->pflichtstunden_kinder_gruppen ?? [])));

        return Child::query()
            ->whereIn('id', $childIds)
            ->where(fn ($q) => $q->whereNotNull('class_id')->orWhereNotNull('group_id'))
            ->when($groups !== [], fn ($q) => $q->where(fn ($g) => $g->whereIn('class_id', $groups)->orWhereIn('group_id', $groups)))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Fasst Familien zusammen, die (transitiv) ein Kind teilen.
     *
     * @param  Collection<int, FamilyUnit>  $families
     * @return list<list<FamilyUnit>>
     */
    private function combineFamilies(Collection $families, array $familyChildren, array $familiesOfChild): array
    {
        $parent = [];
        $find = function (string $key) use (&$parent, &$find): string {
            if (($parent[$key] ?? $key) === $key) {
                return $key;
            }

            return $parent[$key] = $find($parent[$key]);
        };

        foreach ($familiesOfChild as $keys) {
            $keys = array_keys($keys);
            $root = $find($keys[0]);
            foreach (array_slice($keys, 1) as $key) {
                $other = $find($key);
                if ($other !== $root) {
                    $parent[$other] = $root;
                }
            }
        }

        $groups = [];
        foreach ($families as $family) {
            $groups[$find($family->key)][] = $family;
        }

        return array_values($groups);
    }

    /**
     * @param  list<FamilyUnit>  $group
     * @param  list<int>  $childIds
     * @return array{0: int, 1: float} [Soll-Minuten, gezählte Kinder (Anteil)]
     */
    private function requiredMinutes(array $group, array $childIds, array $familyChildren, array $familiesOfChild): array
    {
        $minutesPerUnit = (int) $this->settings->pflichtstunden_anzahl * 60;
        $split = $this->mode() === self::SHARED_SPLIT;
        $share = fn (int $childId): float => $split ? 1 / max(1, count($familiesOfChild[$childId] ?? [])) : 1.0;

        $childShare = array_sum(array_map($share, $childIds));

        if ($this->basis() === self::BASIS_CHILD) {
            $max = $this->settings->pflichtstunden_max_kinder;
            $counted = $max !== null ? min((float) $max, $childShare) : $childShare;

            return [(int) round($minutesPerUnit * $counted), $childShare];
        }

        // Basis Familie: bei „combined“ gilt die Einheit als eine Familie
        if ($this->mode() === self::SHARED_COMBINED) {
            return [$minutesPerUnit, $childShare];
        }

        $family = $group[0];
        $ownChildren = $familyChildren[$family->key] ?? [];
        $familyShare = $ownChildren === [] ? 1.0 : max(array_map($share, $ownChildren));

        return [(int) round($minutesPerUnit * $familyShare), $childShare];
    }
}

<?php

namespace App\Services\Family;

use App\Enums\GuardianRelation;
use App\Model\ChildGuardian;
use App\Model\Family;
use App\Model\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Überführt das bisherige Familienmodell (users.sorg2) in Familien und
 * direkte Kind-Beziehungen. Idempotent, sorg2 bleibt unverändert (Rollback).
 *
 * 1. Paare (A↔B) → Familie (source=migration)
 * 2. Einseitige Verknüpfungen (A→B, B ohne Partner) → ebenfalls Paar, gemeldet
 *    Konflikte (A→B, B→C) / Verweise auf gelöschte Konten → nur gemeldet
 * 3. Einzelpersonen (Eltern/Aufnahme, Kind-Beziehung, Pflichtstunden) → eigene Familie
 * 4. Kinder, die heute nur über den Partner sichtbar sind, werden für den Partner
 *    direkt verknüpft (source=migration, ungeprüft – E3)
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §12.3
 */
class Sorg2MigrationService
{
    /** @var list<array{type: string, user_ids: string, names: string, note: string}> */
    private array $rows = [];

    private array $counts = [];

    public function __construct(
        private readonly FamilyService $families,
        private readonly GroupMembershipService $groups,
    ) {}

    /**
     * @return array{counts: array<string, int>, rows: list<array>}
     */
    public function run(bool $dryRun = false, bool $syncGroups = false): array
    {
        $this->rows = [];
        $this->counts = [
            'familien_paare' => 0,
            'familien_einzeln' => 0,
            'einseitig_repariert' => 0,
            'konflikte' => 0,
            'beziehungen_materialisiert' => 0,
            'bereits_migriert' => 0,
        ];

        // sorg2 bleibt während der Migration exakt erhalten (Rollback auf legacy)
        $dualWrite = config('family.dual_write_sorg2');
        config(['family.dual_write_sorg2' => false]);

        try {
            $pairs = $this->collectPairs();

            foreach ($pairs as [$a, $b]) {
                $this->migratePair($a, $b, $dryRun, $syncGroups);
            }

            $this->migrateSingles($dryRun);
        } finally {
            config(['family.dual_write_sorg2' => $dualWrite]);
        }

        return ['counts' => $this->counts, 'rows' => $this->rows];
    }

    /**
     * @return list<array{0: User, 1: User}>
     */
    private function collectPairs(): array
    {
        $linked = User::query()->whereNotNull('sorg2')->orderBy('id')->get()->keyBy('id');
        $partners = User::withTrashed()->whereIn('id', $linked->pluck('sorg2')->unique())->get()->keyBy('id');

        $pairs = [];
        $used = [];

        foreach ($linked as $a) {
            if (isset($used[$a->id])) {
                continue;
            }

            $b = $partners->get((int) $a->sorg2);

            if ($b === null || $b->trashed() || $b->id === $a->id) {
                $this->counts['konflikte']++;
                $this->report('konflikt', [$a], 'sorg2 verweist auf nicht vorhandenes/gelöschtes Konto '.$a->sorg2);

                continue;
            }

            if ((int) $b->sorg2 === $a->id) {
                $pairs[] = [$a, $b];
                $used[$a->id] = $used[$b->id] = true;

                continue;
            }

            if ($b->sorg2 === null && ! isset($used[$b->id])) {
                $pairs[] = [$a, $b];
                $used[$a->id] = $used[$b->id] = true;
                $this->counts['einseitig_repariert']++;
                $this->report('einseitig', [$a, $b], 'Nur einseitig verknüpft – als Paar übernommen');

                continue;
            }

            $this->counts['konflikte']++;
            $this->report('konflikt', [$a, $b], 'Partner ist mit einem anderen Konto ('.$b->sorg2.') verknüpft – keine gemeinsame Familie');
        }

        return $pairs;
    }

    private function migratePair(User $a, User $b, bool $dryRun, bool $syncGroups): void
    {
        $familyA = $a->family_id;
        $familyB = $b->family_id;

        if ($familyA !== null && $familyA === $familyB) {
            $this->counts['bereits_migriert']++;
        } elseif ($familyA !== null && $familyB !== null) {
            $this->counts['konflikte']++;
            $this->report('konflikt', [$a, $b], 'Partner sind bereits unterschiedlichen Familien zugeordnet');
        } else {
            $this->counts['familien_paare']++;
            $this->report('familie', [$a, $b], $familyA || $familyB ? 'Partner zur bestehenden Familie hinzugefügt' : 'Familie aus sorg2-Paar angelegt');

            if (! $dryRun) {
                DB::transaction(function () use ($a, $b) {
                    $existing = Family::find($a->family_id ?? $b->family_id);
                    if ($existing !== null) {
                        $this->families->addMember($existing, $a->family_id === null ? $a : $b);
                    } else {
                        $this->families->create([$a, $b], null, Family::SOURCE_MIGRATION);
                    }
                });
            }
        }

        $this->materialize($a, $b, $dryRun, $syncGroups);
        $this->materialize($b, $a, $dryRun, $syncGroups);
    }

    /**
     * Verknüpft Kinder von $from, die $to bisher nur über sorg2 sah, direkt mit $to.
     */
    private function materialize(User $from, User $to, bool $dryRun, bool $syncGroups): void
    {
        $existing = DB::table('child_user')->where('user_id', $to->id)->pluck('child_id')->all();

        $missing = DB::table('child_user')
            ->join('children', 'children.id', '=', 'child_user.child_id')
            ->whereNull('children.deleted_at')
            ->where('child_user.user_id', $from->id)
            ->whereNotIn('child_user.child_id', $existing)
            ->get(['child_user.child_id', 'child_user.has_custody', 'child_user.receives_information', 'child_user.can_manage', 'children.first_name', 'children.last_name']);

        foreach ($missing as $link) {
            $this->counts['beziehungen_materialisiert']++;
            $this->report('beziehung', [$to], "Kind {$link->first_name} {$link->last_name} (ID {$link->child_id}) von {$from->name} übernommen – bitte prüfen");

            if (! $dryRun) {
                DB::table('child_user')->insert([
                    'child_id' => $link->child_id,
                    'user_id' => $to->id,
                    'relation' => GuardianRelation::LegalGuardian->value,
                    'has_custody' => $link->has_custody,
                    'receives_information' => $link->receives_information,
                    'can_manage' => $link->can_manage,
                    'source' => ChildGuardian::SOURCE_MIGRATION,
                    'is_auto_provisioned' => false,
                    'reviewed_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (! $dryRun && $syncGroups && $missing->isNotEmpty()) {
            $this->groups->syncDerivedGroups($to);
        }
    }

    private function migrateSingles(bool $dryRun): void
    {
        foreach ($this->singleCandidates() as $user) {
            $this->counts['familien_einzeln']++;
            $this->report('familie', [$user], 'Eigene Familie (ohne Partner)');

            if (! $dryRun) {
                $this->families->create([$user], null, Family::SOURCE_MIGRATION);
            }
        }
    }

    /**
     * Personen ohne Familie, die eine brauchen: Rolle Eltern/Aufnahme,
     * Kind-Beziehung oder Pflichtstunden-Berechtigung. Paar-Mitglieder sind
     * zu diesem Zeitpunkt bereits zugeordnet (außer im Dry-Run).
     *
     * @return Collection<int, User>
     */
    private function singleCandidates(): Collection
    {
        $pairedIds = collect($this->rows)
            ->where('type', 'familie')
            ->flatMap(fn ($row) => array_map('intval', explode(',', $row['user_ids'])))
            ->all();

        $ids = User::query()->whereNull('family_id')
            ->where(function ($q) {
                $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['Eltern', 'Aufnahme']))
                    ->orWhereExists(fn ($e) => $e->from('child_user')->whereColumn('child_user.user_id', 'users.id'));
            })
            ->pluck('id');

        try {
            $ids = $ids->merge(User::permission('view Pflichtstunden')->whereNull('family_id')->pluck('id'));
        } catch (PermissionDoesNotExist) {
            // Pflichtstunden-Modul nicht eingerichtet
        }

        return User::query()
            ->whereIn('id', $ids->unique()->diff($pairedIds))
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<User>  $users
     */
    private function report(string $type, array $users, string $note): void
    {
        $this->rows[] = [
            'type' => $type,
            'user_ids' => collect($users)->pluck('id')->implode(','),
            'names' => collect($users)->pluck('name')->implode(' / '),
            'note' => $note,
        ];
    }
}

<?php

namespace App\Console\Commands;

use App\Model\ChildGuardian;
use App\Model\Family;
use App\Model\GuardianLinkReport;
use App\Services\Family\FamilyBuilder;
use App\Services\Family\FamilyResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bereitschaftsprüfung vor dem Umschalten auf das kind-zentrierte Modell
 * (Konzept §14.1). Ändert nichts.
 */
class FamilyStatus extends Command
{
    protected $signature = 'family:status';

    protected $description = 'Zeigt den Stand der Umstellung auf das kind-zentrierte Familienmodell.';

    public function handle(FamilyResolver $resolver, FamilyBuilder $builder): int
    {
        $sorg2Users = DB::table('users')->whereNull('deleted_at')->whereNotNull('sorg2')->get(['id', 'sorg2', 'family_id']);
        $familyById = DB::table('users')->whereIn('id', $sorg2Users->pluck('sorg2'))->pluck('family_id', 'id');
        $unmigratedPairs = $sorg2Users->filter(fn ($u) => $u->family_id === null || $u->family_id !== ($familyById[$u->sorg2] ?? null))->count();

        $usersWithChildrenWithoutFamily = DB::table('users')->whereNull('deleted_at')->whereNull('family_id')
            ->whereExists(fn ($q) => $q->from('child_user')->whereColumn('child_user.user_id', 'users.id'))->count();

        $childrenWithoutGuardian = DB::table('children')->whereNull('deleted_at')
            ->whereNotExists(fn ($q) => $q->from('child_user')->whereColumn('child_user.child_id', 'children.id'))->count();
        $childrenWithoutCustody = DB::table('children')->whereNull('deleted_at')
            ->whereNotExists(fn ($q) => $q->from('child_user')->whereColumn('child_user.child_id', 'children.id')->where('has_custody', true))->count();
        $childrenWithoutExternalId = DB::table('children')->whereNull('deleted_at')->whereNull('external_id')->count();

        $pendingLinks = DB::table('child_user')->where('source', ChildGuardian::SOURCE_MIGRATION)->whereNull('reviewed_at')->count();
        $reviewCases = count($builder->reviewCases());
        $openReports = GuardianLinkReport::query()->open()->count();

        $this->info('Resolver: '.$resolver->mode().' (FAMILY_RESOLVER), Dual-Write sorg2: '.(config('family.dual_write_sorg2') ? 'an' : 'aus'));
        $this->table(['Kennzahl', 'Wert'], [
            ['Familien', Family::count()],
            ['Konten mit sorg2', $sorg2Users->count()],
            ['sorg2-Verknüpfungen ohne gemeinsame Familie', $unmigratedPairs],
            ['Personen mit Kind, aber ohne Familie', $usersWithChildrenWithoutFamily],
            ['Kinder ohne Bezugsperson', $childrenWithoutGuardian],
            ['Kinder ohne sorgeberechtigte Bezugsperson', $childrenWithoutCustody],
            ['Kinder ohne Schüler-ID', $childrenWithoutExternalId],
            ['Übernommene Beziehungen ungeprüft', $pendingLinks],
            ['Klärungsfälle Familienbildung', $reviewCases],
            ['Offene Meldungen „Verbindung ist falsch“', $openReports],
        ]);

        $blockers = [];
        if ($unmigratedPairs > 0) {
            $blockers[] = 'sorg2-Verknüpfungen ohne Familie → php artisan family:migrate-from-sorg2';
        }
        if ($usersWithChildrenWithoutFamily > 0) {
            $blockers[] = 'Personen ohne Familie → php artisan family:rebuild --only-unassigned';
        }

        if ($blockers === []) {
            $this->info('Bereit zum Umschalten (FAMILY_RESOLVER=child_centric). Ungeprüfte Beziehungen, Klärungsfälle und Meldungen können danach abgearbeitet werden.');
        } else {
            $this->warn('Vor dem Umschalten erledigen:');
            foreach ($blockers as $blocker) {
                $this->line(' - '.$blocker);
            }
        }

        return $blockers === [] ? self::SUCCESS : self::FAILURE;
    }
}

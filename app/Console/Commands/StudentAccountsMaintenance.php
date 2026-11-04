<?php

namespace App\Console\Commands;

use App\Model\Child;
use App\Model\User;
use App\Services\Students\StudentAccountLog;
use App\Services\Students\StudentAccountService;
use App\Settings\SchuelerSetting;
use App\Support\StudentAccounts;
use Illuminate\Console\Command;

/**
 * Tägliche Pflege der Schülerkonten (docs/konzept-schuelerkonten.md §7, §9, Pakete S-09/S-11).
 *
 * 1. Konten sperren, deren Kind ausgeschieden oder gelöscht ist.
 * 2. Gesperrte Konten ohne aktives Kind nach `loeschen_nach_tagen` endgültig löschen.
 *    Von der Verwaltung gesperrte Konten mit aktivem Kind bleiben erhalten.
 * 3. Mit „Konten per UCS-Sync anlegen“: Konten für UCS-Kinder in freigegebenen Gruppen anlegen.
 *
 * Bei inaktivem Modul läuft nur 1 (Sperren), damit ausgeschiedene Kinder auch dann keinen
 * Zugang behalten, wenn das Modul später wieder eingeschaltet wird.
 */
class StudentAccountsMaintenance extends Command
{
    protected $signature = 'schuelerkonten:pflege {--dry-run : Nur anzeigen, nichts ändern}';

    protected $description = 'Schülerkonten sperren, löschen und (optional) aus UCS anlegen';

    public function handle(StudentAccountService $accounts, SchuelerSetting $settings): int
    {
        $dry = (bool) $this->option('dry-run');
        $locked = $deleted = $created = 0;

        // 1. Kind ausgeschieden/gelöscht → sperren
        User::students()->where('is_active', true)
            ->whereDoesntHave('studentChild', fn ($q) => $q->where('status', '!=', Child::STATUS_LEFT))
            ->each(function (User $student) use ($accounts, $dry, &$locked) {
                $locked++;
                if (! $dry) {
                    $accounts->deactivate($student, 'kind_ausgeschieden');
                }
            });

        if (StudentAccounts::enabled()) {
            // 2. Löschfrist
            $cutoff = now()->subDays(max(1, (int) $settings->loeschen_nach_tagen));
            User::students()->where('is_active', false)
                ->where(fn ($q) => $q->whereNull('deactivated_at')->orWhere('deactivated_at', '<=', $cutoff))
                ->whereDoesntHave('studentChild', fn ($q) => $q->where('status', '!=', Child::STATUS_LEFT))
                ->each(function (User $student) use ($accounts, $dry, &$deleted) {
                    $deleted++;
                    if (! $dry) {
                        $accounts->delete($student, 'loeschfrist');
                    }
                });

            // 3. UCS-Provisionierung
            $groups = array_map('intval', $settings->freigegebene_gruppen);
            if ($settings->ucs_provisionierung && $groups !== []) {
                Child::query()
                    ->where('status', '!=', Child::STATUS_LEFT)
                    ->whereNull('user_id')
                    ->whereNotNull('ucs_username')
                    ->where('ucs_source', '!=', 'local')
                    ->each(function (Child $child) use ($accounts, $groups, $dry, &$created) {
                        if (array_intersect($groups, $child->derivedGroupIds()) === []) {
                            return;
                        }
                        $created++;
                        if (! $dry) {
                            try {
                                $accounts->createForChild($child, ['ucs_username' => $child->ucs_username, 'ucs_source' => 'kelvin', 'via' => 'ucs']);
                            } catch (\DomainException $e) {
                                StudentAccountLog::warning('Schülerkonto: UCS-Anlage fehlgeschlagen', null, ['kind_id' => $child->id]);
                            }
                        }
                    });
            }
        }

        $this->info(($dry ? '[Probelauf] ' : '')."Gesperrt: {$locked}, gelöscht: {$deleted}, angelegt: {$created}");

        return self::SUCCESS;
    }
}

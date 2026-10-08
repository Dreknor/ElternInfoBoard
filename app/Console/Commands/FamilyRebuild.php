<?php

namespace App\Console\Commands;

use App\Services\Family\FamilyBuilder;
use Illuminate\Console\Command;

/**
 * Bildet Familien automatisch aus den Kind-Beziehungen (§7).
 * Gesperrte (manuell gepflegte) Familien werden nie verändert.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §7.3
 */
class FamilyRebuild extends Command
{
    protected $signature = 'family:rebuild
        {--dry-run : Nur anzeigen, keine Änderungen}
        {--user=* : Nur Komponenten mit diesen User-IDs}
        {--only-unassigned : Nur Personen ohne Familie zuordnen}';

    protected $description = 'Familien aus Kind-Beziehungen bilden bzw. ergänzen (Klärungsfälle werden nur gemeldet).';

    public function handle(FamilyBuilder $builder): int
    {
        $userIds = array_map('intval', (array) $this->option('user'));

        $report = $builder->rebuild(
            onlyUserIds: $userIds === [] ? null : $userIds,
            onlyUnassigned: (bool) $this->option('only-unassigned'),
            dryRun: (bool) $this->option('dry-run'),
        );

        if ($report->dryRun) {
            $this->info('Dry-Run: keine Änderungen geschrieben.');
        }

        $this->table(['Ergebnis', 'Anzahl'], collect($report->summary())->map(fn ($v, $k) => [$k, $v])->values()->all());

        if ($report->reviewCases !== []) {
            $this->warn(count($report->reviewCases).' Klärungsfall/-fälle – siehe `php artisan family:review` bzw. Verwaltung › Familien.');
        }

        return self::SUCCESS;
    }
}

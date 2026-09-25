<?php

namespace App\Console\Commands;

use App\Services\Family\Sorg2MigrationService;
use Illuminate\Console\Command;

/**
 * Datenmigration sorg2 → Familien + direkte Kind-Beziehungen (FAM-09).
 * Idempotent; sorg2 bleibt unverändert, damit FAMILY_RESOLVER=legacy als
 * Rollback funktioniert.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §12.3
 */
class FamilyMigrateFromSorg2 extends Command
{
    protected $signature = 'family:migrate-from-sorg2
        {--dry-run : Nur Report erzeugen, keine Änderungen}
        {--report= : Pfad für den CSV-Report (Default: storage/app/family-migration-<datum>.csv)}
        {--sync-groups : Abgeleitete Gruppen für übernommene Beziehungen sofort anlegen}';

    protected $description = 'Familien aus sorg2-Verknüpfungen anlegen und Partner-Kinder direkt verknüpfen.';

    public function handle(Sorg2MigrationService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info('Dry-Run: keine Änderungen.');
        }

        $result = $service->run($dryRun, (bool) $this->option('sync-groups'));

        $this->table(['Kennzahl', 'Anzahl'], collect($result['counts'])->map(fn ($v, $k) => [$k, $v])->values()->all());

        $path = $this->option('report') ?: storage_path('app/family-migration-'.now()->format('Y-m-d_His').($dryRun ? '-dryrun' : '').'.csv');
        $this->writeCsv($path, $result['rows']);
        $this->info('Report: '.$path);

        if ($result['counts']['beziehungen_materialisiert'] > 0 && ! $dryRun) {
            $this->warn('Übernommene Beziehungen bitte in der Verwaltung prüfen (Familien › Übernommene Beziehungen).');
        }

        return self::SUCCESS;
    }

    private function writeCsv(string $path, array $rows): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        $handle = fopen($path, 'w');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Typ', 'User-IDs', 'Namen', 'Hinweis'], ';');
        foreach ($rows as $row) {
            fputcsv($handle, [$row['type'], $row['user_ids'], $row['names'], $row['note']], ';');
        }
        fclose($handle);
    }
}

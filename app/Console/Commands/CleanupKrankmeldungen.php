<?php

namespace App\Console\Commands;

use App\Model\Krankmeldungen;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupKrankmeldungen extends Command
{
    protected $signature = 'krankmeldungen:cleanup
                            {--dry-run : Nur zählen, nicht löschen}';

    protected $description = 'Löscht Krankmeldungen, die älter als der zuletzt vergangene 1. August sind (inkl. Soft-Deleted und Anhänge).';

    public function handle(): int
    {
        $cutoff = $this->lastAugustFirst();

        $query = Krankmeldungen::withTrashed()->where('created_at', '<', $cutoff);
        $count = $query->count();

        $this->info("Krankmeldungen älter als {$cutoff->format('d.m.Y')}: {$count}");

        if ($this->option('dry-run')) {
            $this->warn('Dry-Run: keine Änderungen.');

            return self::SUCCESS;
        }

        if ($count === 0) {
            return self::SUCCESS;
        }

        $deleted    = 0;
        $mediaCount = 0;

        // Iteration über das Modell, damit Spatie-Medialibrary-Observer
        // die zugehörigen Anhänge sicher aus dem Storage entfernt.
        $query->chunkById(100, function ($items) use (&$deleted, &$mediaCount) {
            foreach ($items as $k) {
                $mediaCount += $k->getMedia()->count();
                $k->forceDelete();
                $deleted++;
            }
        });

        Log::info('Krankmeldungen-Cleanup abgeschlossen', [
            'deleted'      => $deleted,
            'media_files'  => $mediaCount,
            'cutoff'       => $cutoff->toDateString(),
        ]);

        $this->info("✓ {$deleted} Krankmeldungen + {$mediaCount} Anhänge endgültig gelöscht.");

        return self::SUCCESS;
    }

    /**
     * Liefert den jeweils zuletzt vergangenen 1. August (00:00 Uhr).
     */
    private function lastAugustFirst(): Carbon
    {
        return now()->month < 8
            ? now()->subYear()->month(8)->day(1)->startOfDay()
            : now()->month(8)->day(1)->startOfDay();
    }
}


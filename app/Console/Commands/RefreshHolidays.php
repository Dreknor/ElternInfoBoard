<?php

namespace App\Console\Commands;

use App\Model\Holiday;
use App\Services\HolidayService;
use Illuminate\Console\Command;

/**
 * Ferien neu laden und anzeigen – zur Diagnose, wenn im Reinigungsplan oder
 * beim Ferien-Check-In keine Ferien erkannt werden.
 */
class RefreshHolidays extends Command
{
    protected $signature = 'holidays:refresh {year? : Jahr (Standard: laufendes und nächstes Jahr)}';

    protected $description = 'Schulferien des eingestellten Bundeslandes neu laden (ferien-api.de, ersatzweise openholidaysapi.org)';

    public function handle(): int
    {
        $service = app(HolidayService::class);
        $years = $this->argument('year') ? [(int) $this->argument('year')] : [now()->year, now()->year + 1];
        $failed = false;

        foreach ($years as $year) {
            $count = $service->refresh($year);
            if ($count === 0) {
                $failed = true;
                $this->error("{$year}: keine Ferien geladen (Quellen nicht erreichbar oder leer, siehe Log).");

                continue;
            }

            $this->info("{$year}: {$count} Ferienzeiträume geladen.");
            $rows = Holiday::query()
                ->where('year', $year)
                ->where('bundesland', (new \App\Settings\CareSetting)->bundesland)
                ->orderBy('start')
                ->get()
                ->map(fn ($h) => [$h->name, $h->start->format('d.m.Y'), $h->end->format('d.m.Y')]);
            $this->table(['Ferien', 'von', 'bis'], $rows->all());
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

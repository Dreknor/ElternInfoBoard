<?php

namespace App\Console\Commands;

use App\Services\PflichtstundenFamilyService;
use App\Settings\PflichtstundenSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PflichtstundenJahresabschlussCommand extends Command
{
    protected $signature = 'pflichtstunden:jahresabschluss
                            {--start= : Startdatum des abzuschließenden Zeitraums (Y-m-d)}
                            {--end= : Enddatum des abzuschließenden Zeitraums (Y-m-d)}
                            {--dry-run : Nur berechnen, nicht schreiben}';

    protected $description = 'Schließt Pflichtstunden-Konten für einen Zeitraum ab und schreibt Überträge';

    public function handle(PflichtstundenSetting $settings): int
    {
        $service = new PflichtstundenFamilyService($settings);

        $resolvedPeriod = $this->resolvePeriod($service);
        if ($resolvedPeriod === null) {
            return Command::FAILURE;
        }

        [$periodStart, $periodEnd] = $resolvedPeriod;
        $periodYear = $service->periodStartYear($periodStart);

        $this->info("Jahresabschluss Zeitraum {$periodStart->format('d.m.Y')} - {$periodEnd->format('d.m.Y')}");

        $summaries = $service->buildFamilySummaries($periodStart, $periodEnd, ! $this->option('dry-run'));

        $rows = $summaries->map(function (array $summary) {
            return [
                $summary['family_name'],
                $summary['required_hours'].'h',
                $this->formatMinutes($summary['opening_balance_minutes']),
                $this->formatMinutes($summary['totalMinutes']),
                $this->formatMinutes($summary['closing_balance_minutes']),
                $this->formatMinutes($summary['carryover_preview_minutes']),
                number_format($summary['beitrag'], 2, ',', '.').' €',
            ];
        })->all();

        $this->table(
            ['Familie', 'Soll', 'Startsaldo', 'Geleistet', 'Endsaldo', 'Übertrag', 'Beitrag'],
            $rows
        );

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: Es wurden keine Konten geschrieben.');
        } else {
            $this->info('Jahresabschluss und Überträge wurden geschrieben.');
        }

        return Command::SUCCESS;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(PflichtstundenFamilyService $service): ?array
    {
        $startOption = $this->option('start');
        $endOption = $this->option('end');

        if ($startOption || $endOption) {
            if (! $startOption || ! $endOption) {
                $this->error('Wenn ein individueller Zeitraum gesetzt wird, müssen --start und --end zusammen angegeben werden.');
                return null;
            }

            try {
                $start = Carbon::createFromFormat('Y-m-d', (string) $startOption)->startOfDay();
            } catch (\Throwable) {
                $this->error('Ungültiges Startdatum. Erwartet wird das Format Y-m-d.');
                return null;
            }

            try {
                $end = Carbon::createFromFormat('Y-m-d', (string) $endOption)->endOfDay();
            } catch (\Throwable) {
                $this->error('Ungültiges Enddatum. Erwartet wird das Format Y-m-d.');
                return null;
            }

            if ($end->lt($start)) {
                $this->error('Das Enddatum muss nach dem Startdatum liegen.');
                return null;
            }

            return [$start, $end];
        }

        return $service->resolvePeriod(null);
    }

    private function formatMinutes(int $minutes): string
    {
        $sign = $minutes < 0 ? '-' : '';
        $abs = abs($minutes);

        return $sign.floor($abs / 60).'h '.($abs % 60).'m';
    }
}

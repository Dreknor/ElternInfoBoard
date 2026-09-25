<?php

namespace App\Exports;

use App\Services\Pflichtstunden\PflichtstundenService;
use App\Services\Pflichtstunden\PflichtstundenUnit;
use App\Settings\PflichtstundenSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Pflichtstunden-Abrechnung je Einheit (Familie bzw. zusammengefasste Familien).
 * Berechnung ausschließlich über den PflichtstundenService.
 */
class PflichtstundenExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected PflichtstundenSetting $settings;

    protected PflichtstundenService $service;

    protected ?int $year;

    protected Carbon $startDate;

    protected Carbon $endDate;

    public function __construct(?int $year = null)
    {
        $this->settings = app(PflichtstundenSetting::class);
        $this->service = app(PflichtstundenService::class);
        $this->year = $year;

        [$this->startDate, $this->endDate] = $this->service->periodForYear($year);
    }

    /**
     * @return Collection<int, PflichtstundenUnit>
     */
    public function collection()
    {
        return $this->service->units([$this->startDate, $this->endDate]);
    }

    /**
     * @param  PflichtstundenUnit  $unit
     */
    public function map($unit): array
    {
        return [
            $unit->label,
            $unit->memberNames(),
            $this->formatShare($unit->childShare),
            $this->formatMinutes($unit->requiredMinutes),
            $this->formatMinutes($unit->doneMinutes),
            $this->formatMinutes($unit->openMinutes()),
            number_format($unit->beitrag(), 2, ',', '.').' €',
            round($unit->percent(), 2).'%',
        ];
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Familie',
            'Mitglieder',
            'Kinder (Anteil)',
            'Soll',
            'Geleistete Stunden',
            'Offene Stunden',
            'Zu zahlender Beitrag',
            'Erfüllung',
        ];
    }

    public function title(): string
    {
        if ($this->year) {
            return 'Pflichtstunden '.$this->year.'-'.($this->year + 1);
        }

        return 'Pflichtstunden Abrechnung';
    }

    private function formatShare(float $share): string
    {
        return rtrim(rtrim(number_format($share, 2, ',', ''), '0'), ',');
    }

    /**
     * Formatiert Minuten in Std. und Min.
     */
    private function formatMinutes(int $minutes): string
    {
        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;

            return $hours.' Std. '.$mins.' Min.';
        }

        return $minutes.' Min.';
    }
}

<?php

namespace App\Exports;

use App\Services\PflichtstundenFamilyService;
use App\Settings\PflichtstundenSetting;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PflichtstundenExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected PflichtstundenSetting $settings;

    protected PflichtstundenFamilyService $familyService;

    protected ?int $year;

    protected ?string $customLabel;

    protected \Carbon\Carbon $startDate;

    protected \Carbon\Carbon $endDate;

    public function __construct(?int $year = null, ?\Carbon\Carbon $startDate = null, ?\Carbon\Carbon $endDate = null, ?string $customLabel = null)
    {
        $this->settings = new PflichtstundenSetting;
        $this->familyService = new PflichtstundenFamilyService($this->settings);
        $this->year = $year;
        $this->customLabel = $customLabel;

        if ($startDate && $endDate) {
            $this->startDate = $startDate;
            $this->endDate = $endDate;
        } else {
            [$this->startDate, $this->endDate] = $this->familyService->resolvePeriod($year);
        }
    }

    public function collection()
    {
        return $this->familyService->buildFamilySummaries($this->startDate, $this->endDate, true);
    }

    public function map($item): array
    {
        $saldo = $this->formatMinutes($item['closing_balance_minutes']);
        $modeLabel = $this->familyService->modeLabel($item);

        return [
            $item['family_name'],
            $modeLabel,
            number_format((float) $item['required_hours'], 2, ',', '.').' Std.',
            $this->formatMinutes((int) $item['opening_balance_minutes']),
            $this->formatMinutes((int) $item['totalMinutes']),
            $this->formatMinutes((int) $item['openMinutes']),
            $saldo,
            $this->formatMinutes((int) $item['carryover_preview_minutes']),
            number_format((float) $item['beitrag'], 2, ',', '.').' €',
            round((float) $item['percent'], 2).'%',
        ];
    }

    public function headings(): array
    {
        return [
            'Familie',
            'Soll-Modell',
            'Sollstunden',
            'Startsaldo',
            'Geleistete Stunden',
            'Offene Stunden',
            'Kontostand',
            'Übertrag',
            'Zu zahlender Beitrag',
            'Erfüllung',
        ];
    }

    public function title(): string
    {
        if ($this->customLabel) {
            return 'Pflichtstunden '.$this->customLabel;
        }

        if ($this->year) {
            return 'Pflichtstunden '.$this->year.'-'.($this->year + 1);
        }

        return 'Pflichtstunden Abrechnung';
    }

    private function formatMinutes(int $minutes): string
    {
        $sign = $minutes < 0 ? '-' : '';
        $abs = abs($minutes);
        $hours = floor($abs / 60);
        $mins = $abs % 60;

        return $sign.$hours.' Std. '.$mins.' Min.';
    }
}

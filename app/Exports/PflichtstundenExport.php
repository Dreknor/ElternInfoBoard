<?php

namespace App\Exports;

use App\Model\User;
use App\Settings\PflichtstundenSetting;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PflichtstundenExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected PflichtstundenSetting $settings;

    protected ?int $year;

    protected Carbon $startDate;

    protected Carbon $endDate;

    public function __construct(?int $year = null)
    {
        $this->settings = new PflichtstundenSetting;
        $this->year = $year;

        // Zeitraum berechnen
        if ($year) {
            // Spezifisches Jahr
            $this->startDate = Carbon::createFromFormat('Y-m-d', $year.'-'.$this->settings->pflichtstunden_start)->startOfDay();
            $this->endDate = Carbon::createFromFormat('Y-m-d', ($year + 1).'-'.$this->settings->pflichtstunden_ende)->endOfDay();
        } else {
            // Aktueller Zeitraum
            $this->startDate = Carbon::createFromFormat('m-d', $this->settings->pflichtstunden_start)->startOfDay();
            if ($this->startDate->isFuture()) {
                $this->startDate->subYear();
            }
            $this->endDate = Carbon::createFromFormat('m-d', $this->settings->pflichtstunden_ende)->endOfDay();
            if ($this->endDate->isPast()) {
                $this->endDate->addYear();
            }
        }
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Hole alle Nutzer mit Permission "view Pflichtstunden"
        $users = User::permission('view Pflichtstunden')
            ->with(['pflichtstunden' => function ($query) {
                $query->where('approved', true)
                    ->whereBetween('start', [$this->startDate, $this->endDate]);
            }])
            ->get();

        // Gruppiere nach Hauptnutzer (berücksichtige sorg2-Verknüpfung)
        $grouped = collect();
        $processed = collect();

        foreach ($users as $user) {
            // Überspringe wenn bereits als sorg2 verarbeitet
            if ($processed->contains($user->id)) {
                continue;
            }

            // Finde verknüpfte Person
            $partner = null;
            if ($user->sorg2) {
                $partner = $users->where('id', $user->sorg2)->first();
                if ($partner) {
                    $processed->push($partner->id);
                }
            }

            $grouped->push([
                'user' => $user,
                'partner' => $partner,
            ]);

            $processed->push($user->id);
        }

        return $grouped;
    }

    public function map($item): array
    {
        $user = $item['user'];
        $partner = $item['partner'];

        // Berechne geleistete Minuten
        $totalMinutes = $user->pflichtstunden->sum('duration');
        if ($partner) {
            $totalMinutes += $partner->pflichtstunden->sum('duration');
        }

        // Berechne erforderliche Minuten
        $requiredMinutes = $this->settings->pflichtstunden_anzahl * 60;

        // Berechne offene Minuten
        $openMinutes = max(0, $requiredMinutes - $totalMinutes);

        // Berechne Beitrag (nur wenn Stunden nicht erfüllt)
        $beitrag = 0;
        if ($openMinutes > 0) {
            $openHours = $openMinutes / 60;
            $beitrag = $openHours * $this->settings->pflichtstunden_betrag;
        }

        // Namen zusammenstellen
        $name = $user->name;
        if ($partner) {
            $name .= ' / '.$partner->name;
        }

        // Formatiere Stunden
        $geleistetFormatted = $this->formatMinutes($totalMinutes);
        $offenFormatted = $this->formatMinutes($openMinutes);

        return [
            $name,
            $geleistetFormatted,
            $offenFormatted,
            number_format($beitrag, 2, ',', '.').' €',
            round(min(100, ($totalMinutes / $requiredMinutes) * 100), 2).'%',
        ];
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Familie',
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

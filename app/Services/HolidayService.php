<?php

namespace App\Services;

use App\Model\Holiday;
use App\Settings\CareSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HolidayService
{
    protected string $bundesland;

    public function __construct(?string $bundesland = null)
    {
        $this->bundesland = $bundesland ?? (new CareSetting)->bundesland;
    }

    /**
     * Prüft, ob heute ein Ferientag ist.
     */
    public function isTodayHoliday(): bool
    {
        return $this->isHoliday(now());
    }

    /**
     * Prüft, ob ein bestimmtes Datum ein Ferientag ist.
     */
    public function isHoliday(Carbon $date): bool
    {
        $holidays = $this->getHolidaysForYear($date->year);

        foreach ($holidays as $holiday) {
            if ($date->between($holiday->start, $holiday->end)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lädt Ferientage für ein bestimmtes Jahr.
     * Holt sie aus der DB oder von der API falls nicht vorhanden.
     */
    public function getHolidaysForYear(int $year): \Illuminate\Support\Collection
    {
        $holidays = Holiday::query()
            ->where('year', $year)
            ->where('bundesland', $this->bundesland)
            ->get();

        if ($holidays->isNotEmpty()) {
            return $holidays;
        }

        return $this->fetchAndStoreHolidays($year);
    }

    /**
     * Holt Ferien von der API und speichert sie in der Datenbank.
     */
    protected function fetchAndStoreHolidays(int $year): \Illuminate\Support\Collection
    {
        try {
            $cacheKey = 'ferien_' . $this->bundesland . '_' . $year;

            $ferien = Cache::remember($cacheKey, now()->diff(Carbon::now()->endOfYear()), function () use ($year) {
                $url = 'https://ferien-api.de/api/v1/holidays/' . $this->bundesland . '/' . $year;

                return json_decode(file_get_contents($url), true);
            });

            if (is_array($ferien) && !empty($ferien)) {
                foreach ($ferien as $ferieTage) {
                    Holiday::query()->updateOrCreate(
                        [
                            'year' => $year,
                            'name' => $ferieTage['name'] ?? 'Ferien',
                            'start' => $ferieTage['start'],
                            'end' => $ferieTage['end'],
                            'bundesland' => $this->bundesland,
                        ],
                        [
                            'year' => $year,
                            'name' => $ferieTage['name'] ?? 'Ferien',
                            'start' => $ferieTage['start'],
                            'end' => $ferieTage['end'],
                            'bundesland' => $this->bundesland,
                        ]
                    );
                }

                return Holiday::query()
                    ->where('year', $year)
                    ->where('bundesland', $this->bundesland)
                    ->get();
            }
        } catch (\Exception $e) {
            Log::error('Error fetching holidays from API: ' . $e->getMessage());
        }

        return collect();
    }

    /**
     * Cache für ein Bundesland invalidieren (z.B. nach Wechsel des Bundeslandes).
     */
    public function clearCache(?int $year = null): void
    {
        $year = $year ?? Carbon::now()->year;
        Cache::forget('ferien_' . $this->bundesland . '_' . $year);
    }

    /**
     * Gibt alle verfügbaren Bundesländer als Key-Value-Array zurück.
     */
    public static function bundeslaender(): array
    {
        return [
            'BW' => 'Baden-Württemberg',
            'BY' => 'Bayern',
            'BE' => 'Berlin',
            'BB' => 'Brandenburg',
            'HB' => 'Bremen',
            'HH' => 'Hamburg',
            'HE' => 'Hessen',
            'MV' => 'Mecklenburg-Vorpommern',
            'NI' => 'Niedersachsen',
            'NW' => 'Nordrhein-Westfalen',
            'RP' => 'Rheinland-Pfalz',
            'SL' => 'Saarland',
            'SN' => 'Sachsen',
            'ST' => 'Sachsen-Anhalt',
            'SH' => 'Schleswig-Holstein',
            'TH' => 'Thüringen',
        ];
    }
}


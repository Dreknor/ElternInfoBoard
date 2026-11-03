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

    /** @var array<int, \Illuminate\Support\Collection> Ferien je Jahr (pro Instanz zwischengespeichert) */
    protected array $loaded = [];

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
        return $this->holidayOn($date) !== null;
    }

    /**
     * Liefert die Ferien, in die das Datum fällt (oder null).
     *
     * Berücksichtigt auch die Ferien des Vorjahres: Die Weihnachtsferien werden
     * unter dem Jahr ihres Beginns gespeichert, reichen aber in den Januar.
     */
    public function holidayOn(Carbon $date): ?Holiday
    {
        $day = $date->copy()->startOfDay();

        foreach ([$day->year, $day->year - 1] as $year) {
            foreach ($this->holidaysFor($year) as $holiday) {
                if ($day->between($holiday->start->copy()->startOfDay(), $holiday->end->copy()->endOfDay())) {
                    return $holiday;
                }
            }
        }

        return null;
    }

    /**
     * Ermittelt, ob die Woche des Datums eine Ferienwoche ist.
     *
     * Eine Woche gilt als Ferienwoche, wenn mindestens $minDays ihrer Schultage
     * (Montag bis Freitag) in den Ferien liegen. Standard: 3 von 5, d. h. die
     * Mehrheit der Schultage. Zurückgegeben werden die Ferien mit den meisten
     * Tagen in dieser Woche, sonst null.
     */
    public function holidayForWeek(Carbon $date, int $minDays = 3): ?Holiday
    {
        $monday = $date->copy()->startOfWeek()->startOfDay();
        $counts = [];
        $byId = [];

        for ($i = 0; $i < 5; $i++) {
            $holiday = $this->holidayOn($monday->copy()->addDays($i));
            if ($holiday) {
                $counts[$holiday->id] = ($counts[$holiday->id] ?? 0) + 1;
                $byId[$holiday->id] = $holiday;
            }
        }

        if (array_sum($counts) < $minDays) {
            return null;
        }

        arsort($counts);

        return $byId[array_key_first($counts)];
    }

    /**
     * Ferien eines Jahres, pro Instanz zwischengespeichert. Verhindert, dass bei
     * fehlender Ferien-API für jede Woche erneut eine Anfrage gestellt wird.
     */
    protected function holidaysFor(int $year): \Illuminate\Support\Collection
    {
        return $this->loaded[$year] ??= $this->getHolidaysForYear($year);
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


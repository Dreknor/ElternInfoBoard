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
     * Holt die Ferien eines Jahres und speichert sie in der Datenbank.
     *
     * Quelle 1: ferien-api.de, Quelle 2 (Ausweichquelle): openholidaysapi.org.
     * Gespeichert werden nur Ferien, die im Jahr beginnen (Weihnachtsferien
     * zählen zum Jahr ihres Beginns, siehe holidayOn()). Schlägt der Abruf
     * fehl, wird er eine Stunde lang nicht wiederholt.
     */
    protected function fetchAndStoreHolidays(int $year): \Illuminate\Support\Collection
    {
        $failKey = 'ferien_fetch_failed_' . $this->bundesland . '_' . $year;
        if (Cache::has($failKey)) {
            return collect();
        }

        $ferien = $this->fromFerienApi($year) ?? $this->fromOpenHolidays($year);

        if ($ferien === null) {
            Cache::put($failKey, true, now()->addHour());
            Log::warning("Ferien für {$this->bundesland} {$year} konnten von keiner Quelle geladen werden.");

            return collect();
        }

        foreach ($ferien as $f) {
            Holiday::query()->updateOrCreate(
                ['year' => $year, 'bundesland' => $this->bundesland, 'start' => $f['start'], 'end' => $f['end']],
                ['name' => $f['name']]
            );
        }

        return Holiday::query()
            ->where('year', $year)
            ->where('bundesland', $this->bundesland)
            ->get();
    }

    /**
     * Ferien eines Jahres neu laden (z. B. nach Ausfall der Ferien-API).
     *
     * @return int Anzahl der geladenen Ferienzeiträume
     */
    public function refresh(int $year): int
    {
        Cache::forget('ferien_fetch_failed_' . $this->bundesland . '_' . $year);
        unset($this->loaded[$year]);

        $existing = Holiday::query()->where('year', $year)->where('bundesland', $this->bundesland);
        $backup = $existing->get();
        $existing->delete();

        $loaded = $this->fetchAndStoreHolidays($year);

        // Abruf fehlgeschlagen: bisherige Daten behalten
        if ($loaded->isEmpty() && $backup->isNotEmpty()) {
            foreach ($backup as $holiday) {
                Holiday::query()->create($holiday->only(['year', 'bundesland', 'name', 'start', 'end']));
            }

            return 0;
        }

        return $loaded->count();
    }

    /**
     * @return list<array{name: string, start: string, end: string}>|null
     */
    protected function fromFerienApi(int $year): ?array
    {
        try {
            $response = $this->http()->get('https://ferien-api.de/api/v1/holidays/' . $this->bundesland . '/' . $year);
            if (! $response->successful() || ! is_array($response->json())) {
                Log::warning('ferien-api.de antwortet nicht wie erwartet', ['status' => $response->status()]);

                return null;
            }

            return $this->normalize($year, collect($response->json())->map(fn ($f) => [
                'name' => $f['name'] ?? 'Ferien',
                'start' => $f['start'] ?? null,
                'end' => $f['end'] ?? null,
            ]));
        } catch (\Throwable $e) {
            Log::warning('ferien-api.de nicht erreichbar: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @return list<array{name: string, start: string, end: string}>|null
     */
    protected function fromOpenHolidays(int $year): ?array
    {
        try {
            $response = $this->http()->get('https://openholidaysapi.org/SchoolHolidays', [
                'countryIsoCode' => 'DE',
                'subdivisionCode' => 'DE-' . $this->bundesland,
                'languageIsoCode' => 'DE',
                'validFrom' => $year . '-01-01',
                'validTo' => $year . '-12-31',
            ]);
            if (! $response->successful() || ! is_array($response->json())) {
                Log::warning('openholidaysapi.org antwortet nicht wie erwartet', ['status' => $response->status()]);

                return null;
            }

            return $this->normalize($year, collect($response->json())->map(fn ($f) => [
                'name' => collect($f['name'] ?? [])->firstWhere('language', 'DE')['text']
                    ?? ($f['name'][0]['text'] ?? 'Ferien'),
                'start' => $f['startDate'] ?? null,
                'end' => $f['endDate'] ?? null,
            ]));
        } catch (\Throwable $e) {
            Log::warning('openholidaysapi.org nicht erreichbar: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Datum als Y-m-d, nur Ferien mit Beginn im Jahr. Leere Antwort = Fehler (null).
     *
     * @return list<array{name: string, start: string, end: string}>|null
     */
    private function normalize(int $year, \Illuminate\Support\Collection $ferien): ?array
    {
        $result = $ferien
            ->filter(fn ($f) => $f['start'] && $f['end'])
            ->map(fn ($f) => [
                'name' => (string) $f['name'],
                'start' => Carbon::parse($f['start'])->toDateString(),
                'end' => Carbon::parse($f['end'])->toDateString(),
            ])
            ->filter(fn ($f) => (int) substr($f['start'], 0, 4) === $year)
            ->values()
            ->all();

        return $result === [] ? null : $result;
    }

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return \Illuminate\Support\Facades\Http::timeout(10)
            ->connectTimeout(5)
            ->acceptJson()
            ->withUserAgent('ElternInfoBoard (' . config('app.url') . ')');
    }

    /**
     * Cache für ein Bundesland invalidieren (z.B. nach Wechsel des Bundeslandes).
     */
    public function clearCache(?int $year = null): void
    {
        $year = $year ?? Carbon::now()->year;
        Cache::forget('ferien_' . $this->bundesland . '_' . $year);
        Cache::forget('ferien_fetch_failed_' . $this->bundesland . '_' . $year);
        unset($this->loaded[$year]);
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


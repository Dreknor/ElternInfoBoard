<?php

namespace App\Console\Commands\Demo;

use App\Model\Stundenplan\Eintrag;
use App\Model\Stundenplan\Fach;
use App\Model\Stundenplan\Klasse;
use App\Model\Stundenplan\Lehrer;
use App\Model\Stundenplan\Raum;
use App\Model\Stundenplan\Schuljahr;
use App\Model\Stundenplan\Zeitslot;
use App\Services\StundenplanDataProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Demo-Daten (demo:setup). Wird in App\Console\Commands\DemoSetup eingebunden und
 * nutzt dessen Zustand: $this->users, $this->groups (IDs), $this->children.
 *
 * Stundenplan einer Grundschule (Klassen 1a, 1b, 2a, 3b, 4a) und Vertretungsplan-
 * Extras (A/B-Wochen, Abwesenheiten). Idempotent: Einträge tragen eine feste
 * `unterrichts_id` (DEMO-…) und werden bei einem erneuten Lauf aktualisiert.
 */
trait SeedsStundenplan
{
    /** Zeitraster: Stunde => [von, bis]. Lücken = Pausen (Frühstück/Hof 9:20–9:50, Mittag 11:30–11:50). */
    private array $demoZeitraster = [
        1 => ['07:45', '08:30'],
        2 => ['08:35', '09:20'],
        3 => ['09:50', '10:35'],
        4 => ['10:45', '11:30'],
        5 => ['11:50', '12:35'],
        6 => ['12:45', '13:30'],
    ];

    private function createStundenplan(): void
    {
        $schuljahr = $this->createDemoSchuljahr();

        foreach ($this->demoZeitraster as $stunde => [$von, $bis]) {
            Zeitslot::updateOrCreate(
                ['schuljahr_id' => $schuljahr->id, 'stunde' => $stunde],
                ['zeit_von' => $von, 'zeit_bis' => $bis],
            );
        }
        $zeitslots = Zeitslot::where('schuljahr_id', $schuljahr->id)->pluck('id', 'stunde');

        // Fächer (Kürzel passen zu den Farben in den Stundenplan-Ansichten)
        $faecher = [];
        foreach ([
            'DE' => ['Deutsch', '#EAB308'],
            'MA' => ['Mathematik', '#3B82F6'],
            'SU' => ['Sachunterricht', '#14B8A6'],
            'EN' => ['Englisch', '#EF4444'],
            'SP' => ['Sport', '#22C55E'],
            'MU' => ['Musik', '#EC4899'],
            'KU' => ['Kunst', '#F97316'],
            'WE' => ['Werken', '#A16207'],
            'RE' => ['Ev. Religion', '#6366F1'],
            'CH' => ['Schulchor', '#D946EF'],
        ] as $kuerzel => [$name, $farbe]) {
            $faecher[$kuerzel] = $this->demoRestored(Fach::withTrashed()->updateOrCreate(
                ['kuerzel' => $kuerzel],
                ['name' => $name, 'farbe' => $farbe],
            ));
        }

        // Lehrkräfte (Kürzel = Nachname, wie in den Demo-Vertretungen)
        $lehrer = [];
        foreach ([
            'Hoffmann' => ['Christine', 'lehrerin'],
            'Berger' => ['Martin', 'lehrer2'],
            'Schneider' => ['Thomas', 'schulleitung'],
            'Krause' => ['Sabine', null],
            'Lange' => ['Julia', null],
            'Neumann' => ['Katrin', null],
            'Richter' => ['Anna', null],
            'Weber' => ['Stefan', null],
            'Sommer' => ['Ruth', null],
        ] as $kuerzel => [$vorname, $userKey]) {
            $lehrer[$kuerzel] = $this->demoRestored(Lehrer::withTrashed()->updateOrCreate(
                ['kuerzel' => $kuerzel],
                [
                    'name' => $kuerzel,
                    'vorname' => $vorname,
                    'user_id' => $userKey ? ($this->users[$userKey]->id ?? null) : null,
                ],
            ));
        }

        // Räume
        $raeume = [];
        foreach ([
            'R101' => ['Klassenraum 1a', 'Erdgeschoss', 26],
            'R102' => ['Klassenraum 1b', 'Erdgeschoss', 26],
            'R201' => ['Klassenraum 2a', '1. Obergeschoss', 26],
            'R203' => ['Klassenraum 3b', '1. Obergeschoss', 28],
            'R204' => ['Klassenraum 4a', '1. Obergeschoss', 28],
            'TH' => ['Turnhalle', 'Nebengebäude', 60],
            'MUS' => ['Musikraum', 'Erdgeschoss', 30],
            'WERK' => ['Werkraum', 'Untergeschoss', 16],
            'AULA' => ['Aula', 'Erdgeschoss', 150],
        ] as $kuerzel => [$name, $beschreibung, $kapazitaet]) {
            $raeume[$kuerzel] = $this->demoRestored(Raum::withTrashed()->updateOrCreate(
                ['kuerzel' => $kuerzel],
                ['name' => $name, 'beschreibung' => $beschreibung, 'kapazitaet' => $kapazitaet],
            ));
        }

        // Klassen: Kurzform wie in den Vertretungen ('1a'), Name = Gruppenname
        // ('1a – Frösche'), damit StundenplanDataProvider::klassenForChild die Kinder
        // über ihre Klassengruppe zuordnet (normalisierter Namensvergleich).
        $klassenDef = [
            '1a' => ['group' => 'klasse_1a', 'kl' => 'Berger', 'raum' => 'R101', 'sport' => 'Berger', 'religion' => 'Sommer'],
            '1b' => ['group' => 'klasse_1b', 'kl' => 'Krause', 'raum' => 'R102', 'sport' => 'Weber', 'religion' => 'Sommer'],
            '2a' => ['group' => 'klasse_2a', 'kl' => 'Lange', 'raum' => 'R201', 'sport' => 'Weber', 'religion' => 'Sommer'],
            '3b' => ['group' => 'klasse_3b', 'kl' => 'Hoffmann', 'raum' => 'R203', 'sport' => 'Weber', 'religion' => 'Schneider'],
            '4a' => ['group' => 'klasse_4a', 'kl' => 'Neumann', 'raum' => 'R204', 'sport' => 'Weber', 'religion' => 'Schneider'],
        ];
        $klassen = [];
        foreach ($klassenDef as $kurzform => $def) {
            $groupName = isset($this->groups[$def['group']])
                ? DB::table('groups')->where('id', $this->groups[$def['group']])->value('name')
                : null;
            $klassen[$kurzform] = $this->demoRestored(Klasse::withTrashed()->updateOrCreate(
                ['schuljahr_id' => $schuljahr->id, 'kurzform' => $kurzform],
                ['name' => $groupName ?? 'Klasse ' . $kurzform],
            ));
        }

        // Wochenpläne (Mo–Fr, je Tag ab der 1. Stunde). Lehrkräfte- und
        // Raumbelegung ist kollisionsfrei; der Chor (Fr 5.) ist 3b und 4a gemeinsam.
        $plaene = [
            '1a' => [
                1 => ['DE', 'DE', 'MA', 'SU'],
                2 => ['MA', 'DE', 'RE', 'SU', 'SP'],
                3 => ['DE', 'MA', 'KU', 'MU', 'SP'],
                4 => ['MA', 'DE', 'SU', 'WE', 'SP'],
                5 => ['DE', 'MA', 'RE'],
            ],
            '1b' => [
                1 => ['MA', 'DE', 'SP', 'DE', 'SU'],
                2 => ['DE', 'MA', 'SU', 'RE', 'KU'],
                3 => ['MA', 'DE', 'MU', 'SP'],
                4 => ['DE', 'MA', 'WE', 'SU'],
                5 => ['RE', 'DE', 'MA', 'SP'],
            ],
            '2a' => [
                1 => ['DE', 'MA', 'SU', 'SP', 'KU'],
                2 => ['MA', 'DE', 'DE', 'MU'],
                3 => ['DE', 'MA', 'SP', 'RE', 'KU'],
                4 => ['MA', 'DE', 'SU', 'RE'],
                5 => ['DE', 'MA', 'SP', 'SU'],
            ],
            '3b' => [
                1 => ['DE', 'MA', 'SU', 'EN', 'SP'],
                2 => ['DE', 'MA', 'RE', 'SP', 'KU'],
                3 => ['DE', 'EN', 'MA', 'SU', 'MU'],
                4 => ['MA', 'DE', 'SP', 'RE', 'WE'],
                5 => ['DE', 'MA', 'DE', 'SU', 'CH'],
            ],
            '4a' => [
                1 => ['MA', 'DE', 'SU', 'RE', 'MU'],
                2 => ['DE', 'MA', 'SP', 'SU', 'EN'],
                3 => ['MA', 'DE', 'KU', 'WE'],
                4 => ['DE', 'MA', 'SU', 'EN', 'MU', 'SP'],
                5 => ['MA', 'DE', 'RE', 'SU', 'CH', 'SP'],
            ],
        ];

        // Anstehende Demo-Vertretungen: ausgefallenes Fach steht auch im Stundenplan.
        $fachByName = collect($faecher)->mapWithKeys(fn (Fach $f, $k) => [$f->name => $k])->all();
        $vertretungen = DB::table('vertretungen')
            ->whereDate('date', '>=', today())
            ->whereDate('date', '<=', today()->addDays(6))
            ->whereIn('klasse_kurzform', array_keys($plaene))
            ->get();
        foreach ($vertretungen as $v) {
            $tag = Carbon::parse($v->date)->dayOfWeekIso;
            $stunde = (int) $v->stunde;
            $fach = $fachByName[$v->altFach] ?? null;
            if ($tag <= 5 && $fach && $stunde >= 1 && $stunde <= count($this->demoZeitraster)) {
                $plaene[$v->klasse_kurzform][$tag] = array_pad($plaene[$v->klasse_kurzform][$tag], $stunde, 'DE');
                $plaene[$v->klasse_kurzform][$tag][$stunde - 1] = $fach;
            }
        }

        $gemeinsam = ['CH' => ['3b', '4a']];
        $aktiv = [];
        foreach ($plaene as $kurzform => $woche) {
            $def = $klassenDef[$kurzform];
            foreach ($woche as $tag => $stunden) {
                foreach ($stunden as $i => $fach) {
                    $stunde = $i + 1;
                    $klassenIds = isset($gemeinsam[$fach])
                        ? array_map(fn ($k) => $klassen[$k]->id, array_filter(
                            $gemeinsam[$fach],
                            fn ($k) => ($plaene[$k][$tag][$i] ?? null) === $fach,
                        ))
                        : [$klassen[$kurzform]->id];
                    $unterrichtsId = isset($gemeinsam[$fach])
                        ? 'DEMO-' . implode('', $gemeinsam[$fach]) . "-{$tag}-{$stunde}"
                        : "DEMO-{$kurzform}-{$tag}-{$stunde}";
                    if (isset($aktiv[$unterrichtsId])) {
                        continue; // gemeinsamer Unterricht bereits angelegt
                    }

                    $lehrerKuerzel = match ($fach) {
                        'SP' => $def['sport'],
                        'RE' => $def['religion'],
                        'MU', 'EN', 'CH' => 'Richter',
                        default => $def['kl'],
                    };
                    $raumKuerzel = match ($fach) {
                        'SP' => 'TH',
                        'MU' => 'MUS',
                        'WE' => 'WERK',
                        'CH' => 'AULA',
                        default => $def['raum'],
                    };

                    $eintrag = $this->demoRestored(Eintrag::withTrashed()->updateOrCreate(
                        ['schuljahr_id' => $schuljahr->id, 'unterrichts_id' => $unterrichtsId],
                        [
                            'zeitslot_id' => $zeitslots[$stunde],
                            'fach_id' => $faecher[$fach]->id,
                            'wochentag' => $tag,
                            'bemerkung' => $fach === 'CH' ? 'Klassen 3 und 4 gemeinsam' : null,
                        ],
                    ));
                    $eintrag->klassen()->sync($klassenIds);
                    $eintrag->lehrer()->sync([$lehrer[$lehrerKuerzel]->id]);
                    $eintrag->raeume()->sync([$raeume[$raumKuerzel]->id]);
                    $aktiv[$unterrichtsId] = true;
                }
            }
        }

        // Demo-Einträge früherer Läufe, die es im Plan nicht mehr gibt (z. B. andere Vertretungstage)
        Eintrag::withTrashed()->where('schuljahr_id', $schuljahr->id)
            ->where('unterrichts_id', 'like', 'DEMO-%')
            ->whereNotIn('unterrichts_id', array_keys($aktiv))
            ->forceDelete();

        app(StundenplanDataProvider::class)->clearCache();
    }

    private function createDemoSchuljahr(): Schuljahr
    {
        // Schuljahr 1.8.–31.7., das heute umfasst
        $startYear = now()->month >= 8 ? now()->year : now()->year - 1;
        $von = Carbon::create($startYear, 8, 1);
        $bis = Carbon::create($startYear + 1, 7, 31);

        return $this->demoRestored(Schuljahr::withTrashed()->updateOrCreate(
            ['name' => "{$startYear}/" . ($startYear + 1) . ' - Grundschule', 'schulform' => 'Grundschule'],
            [
                'beschreibung' => 'Demo-Stundenplan',
                'datum_von' => $von,
                'datum_bis' => $bis,
                'sw_von' => (int) $von->isoWeek,
                'sw_bis' => (int) $bis->isoWeek,
                'tage_pro_woche' => 5,
                'zeitstempel' => now(),
                'is_active' => true,
            ],
        ));
    }

    private function createVertretungsplanExtras(): void
    {
        // A/B-Wochen: ungerade Kalenderwoche = A-Woche. Datum als Y-m-d speichern,
        // da die Abfragen mit where('week', 'Y-m-d') vergleichen.
        for ($i = 0; $i < 3; $i++) {
            $week = now()->startOfWeek()->addWeeks($i);
            $type = $week->isoWeek % 2 === 1 ? 'A' : 'B';
            $query = DB::table('vertretungsplan_weeks')->whereDate('week', $week->toDateString());
            if ($query->exists()) {
                $query->update(['type' => $type, 'updated_at' => now()]);
            } else {
                DB::table('vertretungsplan_weeks')->insert([
                    'week' => $week->toDateString(),
                    'type' => $type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Abwesenheiten passend zu den Demo-Vertretungen (Hoffmann krank morgen,
        // Fortbildung übermorgen laut Vertretungsplan-News) und eine Klassenfahrt der 4a in der nächsten Woche.
        $tomorrow = Carbon::tomorrow();
        $dayAfter = Carbon::today()->addDays(2);
        $nextWeek = now()->startOfWeek()->addWeek();

        $absences = [
            'demo-1' => ['Hoffmann', $tomorrow, $tomorrow, 'krank'],
            'demo-2' => ['Berger', $dayAfter, $dayAfter, 'Fortbildung'],
            'demo-3' => ['Neumann', $nextWeek->copy()->addDay(), $nextWeek->copy()->addDays(3), 'Klassenfahrt 4a'],
            'demo-4' => ['Klasse 4a', $nextWeek->copy()->addDay(), $nextWeek->copy()->addDays(3), 'Klassenfahrt'],
        ];

        foreach ($absences as $id => [$name, $start, $end, $reason]) {
            $values = [
                'name' => $name,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'reason' => $reason,
                'updated_at' => now(),
            ];
            if (DB::table('vertretungsplan_absences')->where('absence_id', $id)->exists()) {
                DB::table('vertretungsplan_absences')->where('absence_id', $id)->update($values);
            } else {
                DB::table('vertretungsplan_absences')->insert($values + ['absence_id' => $id, 'created_at' => now()]);
            }
        }
    }

    /**
     * Von früheren Läufen weich gelöschte Demo-Datensätze wiederherstellen.
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     * @param  T  $model
     * @return T
     */
    private function demoRestored($model)
    {
        if ($model->trashed()) {
            $model->restore();
        }

        return $model;
    }
}

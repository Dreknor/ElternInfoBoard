<?php

namespace App\Services;

use App\Model\Child;
use App\Model\Group;
use App\Model\Stundenplan\Schuljahr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StundenplanDataProvider
{
    /**
     * Get stundenplan data from database
     * Returns data in the same format as JSON import
     * Now supports multiple active Schuljahre with different Zeitraster
     *
     * @return array|null
     */
    public function getStundenplanData(): ?array
    {
        return Cache::remember('stundenplan_data_from_db', 3600, function () {
            // Get ALL active Schuljahre
            $schuljahre = Schuljahr::where('is_active', true)
                ->with(['zeitslots', 'klassen.eintraege.fach', 'klassen.eintraege.lehrer', 'klassen.eintraege.raeume', 'klassen.eintraege.zeitslot'])
                ->get();

            if ($schuljahre->isEmpty()) {
                return null;
            }

            // Use the first Schuljahr for Basisdaten (can be extended later)
            $mainSchuljahr = $schuljahre->first();

            // Build Basisdaten
            $basisdaten = [
                'DatumVon' => $mainSchuljahr->datum_von->format('d.m.Y'),
                'DatumBis' => $mainSchuljahr->datum_bis->format('d.m.Y'),
                'SwVon' => (string) $mainSchuljahr->sw_von,
                'SwBis' => (string) $mainSchuljahr->sw_bis,
                'TageProWoche' => (string) $mainSchuljahr->tage_pro_woche,
                'Zeitstempel' => $mainSchuljahr->zeitstempel ? $mainSchuljahr->zeitstempel->format('d.m.Y, H:i') : now()->format('d.m.Y, H:i'),
            ];

            // Build Zeitslots - combined from all Schuljahre (will be filtered per class later)
            $zeitslots = [];
            foreach ($schuljahre as $schuljahr) {
                foreach ($schuljahr->zeitslots->sortBy('stunde') as $zeitslot) {
                    $zeitslots[] = [
                        'Stunde' => (string) $zeitslot->stunde,
                        'ZeitVon' => $zeitslot->zeit_von instanceof \Carbon\Carbon
                            ? $zeitslot->zeit_von->format('H:i')
                            : $zeitslot->zeit_von,
                        'ZeitBis' => $zeitslot->zeit_bis instanceof \Carbon\Carbon
                            ? $zeitslot->zeit_bis->format('H:i')
                            : $zeitslot->zeit_bis,
                        'schuljahr_id' => $zeitslot->schuljahr_id, // NEW: Track which Schuljahr
                    ];
                }
            }

            // Build Klassen with Plan from ALL active Schuljahre
            $klassen = [];
            foreach ($schuljahre as $schuljahr) {
                foreach ($schuljahr->klassen->sortBy('kurzform') as $klasse) {
                    $plan = [];

                    foreach ($klasse->eintraege as $eintrag) {
                        $planEntry = [
                            'PlUn' => $eintrag->unterrichts_id,
                            'PlTg' => (string) $eintrag->wochentag,
                            'PlSt' => (string) $eintrag->zeitslot->stunde,
                            'PlFa' => $eintrag->fach ? $eintrag->fach->kuerzel : '',
                            'PlLe' => $eintrag->lehrer->pluck('kuerzel')->toArray(),
                            'PlRa' => $eintrag->raeume->pluck('kuerzel')->toArray(),
                            'PlKl' => $eintrag->klassen->pluck('kurzform')->toArray(),
                        ];

                        $plan[] = $planEntry;
                    }

                    $klassen[] = [
                        'Kurzform' => $klasse->kurzform,
                        'Name' => $klasse->name,
                        'Plan' => $plan,
                        'schuljahr_id' => $klasse->schuljahr_id, // NEW: Track which Schuljahr
                        'schulform' => $schuljahr->schulform, // NEW: Track Schulform
                    ];
                }
            }

            return [
                'Basisdaten' => $basisdaten,
                'Zeitslots' => $zeitslots,
                'Klassen' => $klassen,
                'Schuljahre' => $schuljahre->map(function($sj) {
                    return [
                        'id' => $sj->id,
                        'name' => $sj->name,
                        'schulform' => $sj->schulform,
                        'beschreibung' => $sj->beschreibung,
                    ];
                })->toArray(),
            ];
        });
    }

    /**
     * Stundenplan-Klassen (Kurzformen) eines Kindes.
     *
     * Eine von den Eltern gewählte Klasse (`children.stundenplan_klasse`) hat
     * Vorrang. Sonst wird über den Namen der Gruppen des Kindes zugeordnet
     * (Klasse, Gruppe, weitere Gruppen wie UCS-Kombiklassen): Gruppenname = Kurzform bzw. Name der Stundenplan-Klasse.
     * Passt kein Name exakt, wird ohne Groß-/Kleinschreibung, Leerzeichen und ein
     * vorangestelltes „Klasse“ verglichen („Klasse 3a“ ↔ „3a“).
     *
     * @return list<string>
     */
    public function klassenForChild(Child $child, ?array $data = null): array
    {
        $data ??= $this->getStundenplanData();
        $klassen = $data['Klassen'] ?? [];
        if ($klassen === []) {
            return [];
        }

        $result = [];
        if ($child->stundenplan_klasse && in_array($child->stundenplan_klasse, array_column($klassen, 'Kurzform'), true)) {
            $result[] = $child->stundenplan_klasse;
        }

        $groupIds = array_values(array_unique(array_filter(array_merge(
            [$child->class_id, $child->group_id],
            $child->additionalGroups()->pluck('groups.id')->all(),
        ))));
        if ($groupIds === []) {
            return $result;
        }

        $names = Group::withoutGlobalScopes()->whereIn('id', $groupIds)->pluck('name', 'id');
        // Reihenfolge beibehalten: Klasse vor Gruppe vor weiteren Gruppen
        $names = array_values(array_filter(array_map(fn ($id) => $names[$id] ?? null, $groupIds)));

        foreach ($names as $name) {
            $exact = null;
            $loose = null;
            foreach ($klassen as $klasse) {
                $kurzform = $klasse['Kurzform'] ?? '';
                if ($kurzform === $name) {
                    $exact = $kurzform;
                    break;
                }
                if ($loose === null && in_array(self::normalize($name), [self::normalize($kurzform), self::normalize($klasse['Name'] ?? '')], true)) {
                    $loose = $kurzform;
                }
            }
            if ($match = $exact ?? $loose) {
                $result[] = $match;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Alle Klassen des aktiven Stundenplans (für die Auswahl in der App).
     *
     * @return list<array{kurzform: string, name: string|null}>
     */
    public function klassen(?array $data = null): array
    {
        $data ??= $this->getStundenplanData();

        return collect($data['Klassen'] ?? [])
            ->map(fn ($k) => ['kurzform' => (string) $k['Kurzform'], 'name' => $k['Name'] ?? null])
            ->unique('kurzform')
            ->sortBy('kurzform', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private static function normalize(string $value): string
    {
        $value = mb_strtolower(preg_replace('/\s+/u', '', $value));

        return preg_replace('/^klasse/u', '', $value) ?: $value;
    }

    /**
     * Clear the cache
     */
    public function clearCache(): void
    {
        Cache::forget('stundenplan_data_from_db');
        Cache::forget('stundenplan_data'); // Old cache key
    }
}


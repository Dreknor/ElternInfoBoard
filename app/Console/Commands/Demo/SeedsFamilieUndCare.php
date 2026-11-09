<?php

namespace App\Console\Commands\Demo;

use App\Enums\GuardianRelation;
use App\Model\ActiveDisease;
use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\ChildNotice;
use App\Model\Disease;
use App\Model\Family;
use App\Model\Holiday;
use App\Model\LatePickup;
use App\Model\User;
use App\Services\Family\FamilyBuilder;
use App\Services\Family\FamilyService;
use App\Services\Family\GuardianshipService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Demo-Daten (demo:setup). Wird in App\Console\Commands\DemoSetup eingebunden und
 * nutzt dessen Zustand: $this->users, $this->groups (IDs), $this->children.
 */
trait SeedsFamilieUndCare
{
    // =========================================================================
    // Familien & Bezugspersonen (kind-zentriertes Familienmodell)
    // =========================================================================
    private function createFamilien(): void
    {
        $guardianship = app(GuardianshipService::class);

        // Zusätzliche Konten: zweiter Elternteil bzw. weitere Bezugsperson
        $extraUsers = [
            'mueller_vater' => ['name' => 'Michael Müller', 'email' => 'michael.mueller@ev-schulzentrum-demo.de'],
            'mueller_oma' => ['name' => 'Ingrid Müller', 'email' => 'ingrid.mueller@ev-schulzentrum-demo.de'],
            'koch_vater' => ['name' => 'Stefan Koch', 'email' => 'stefan.koch@ev-schulzentrum-demo.de'],
        ];
        foreach ($extraUsers as $key => $data) {
            $user = User::withTrashed()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('Demo1234!'),
                    'changePassword' => false,
                    'benachrichtigung' => 'daily',
                    'email_verified_at' => now(),
                ]
            );
            $user->restore();
            $user->syncRoles(['Eltern']);
            $this->users[$key] = $user;
        }

        // Eltern in die Gesamtelternschaft (manuelle Gruppe wie bei den übrigen Eltern)
        foreach (['mueller_vater', 'koch_vater'] as $key) {
            $this->demoAddToGroup($key, 'gesamtelternschaft');
        }
        $this->demoAddToGroup('mueller_vater', 'hort');

        // Beziehungen Kind ↔ Bezugsperson mit Rechten.
        // Die Hauptkonten wurden in createChildren roh (ohne Beziehungsart) verknüpft.
        $relations = [
            ['lena_mueller', 'mueller', GuardianRelation::Mother, []],
            ['lena_mueller', 'mueller_vater', GuardianRelation::Father, []],
            // Großmutter: nur Informationen, kein Sorgerecht, nicht verwalten
            ['lena_mueller', 'mueller_oma', GuardianRelation::Grandparent, []],
            ['max_schulz', 'schulz', GuardianRelation::LegalGuardian, []],
            ['emma_fischer', 'fischer', GuardianRelation::LegalGuardian, []],
            ['paul_koch', 'koch', GuardianRelation::Mother, []],
            ['marie_koch', 'koch', GuardianRelation::Mother, []],
            ['paul_koch', 'koch_vater', GuardianRelation::Father, []],
            ['marie_koch', 'koch_vater', GuardianRelation::Father, []],
            ['tim_bauer', 'bauer', GuardianRelation::LegalGuardian, []],
            ['anna_wagner', 'wagner', GuardianRelation::LegalGuardian, []],
        ];

        foreach ($relations as [$childKey, $userKey, $relation, $rights]) {
            if (! isset($this->children[$childKey], $this->users[$userKey])) {
                continue;
            }
            $child = $this->children[$childKey];
            $user = $this->users[$userKey];

            if ($guardianship->pivot($child, $user) === null) {
                $guardianship->link($child, $user, $relation, $rights);
            } else {
                // Bestehende (rohe) Verknüpfung: Beziehungsart + Standardrechte setzen
                $guardianship->update($child, $user, array_merge(
                    ['relation' => $relation->value],
                    $relation->defaultRights(),
                    $rights,
                ));
            }
        }

        // Familien (Haushalte): Müller und Koch als gesperrte, manuell gepflegte
        // Familien; die Großmutter bildet einen eigenen Haushalt (sie soll z. B.
        // keine Lesebestätigungen oder Pflichtstunden für die Eltern abgeben).
        $this->demoFamily(['mueller', 'mueller_vater'], 'Familie Müller');
        $this->demoFamily(['mueller_oma'], 'Ingrid Müller (Großmutter)');
        $this->demoFamily(['koch', 'koch_vater'], 'Familie Koch');

        // Übrige Eltern: Familien automatisch aus den Kind-Beziehungen bilden (idempotent)
        $parentIds = collect(['schulz', 'fischer', 'bauer', 'wagner'])
            ->filter(fn ($k) => isset($this->users[$k]))
            ->map(fn ($k) => $this->users[$k]->id)
            ->values()
            ->all();
        if ($parentIds !== []) {
            app(FamilyBuilder::class)->rebuild($parentIds);
        }

        foreach ($this->users as $user) {
            $user->refresh();
        }
    }

    /**
     * Legt eine Familie über den FamilyService an bzw. ergänzt sie (idempotent).
     *
     * @param  list<string>  $memberKeys
     */
    private function demoFamily(array $memberKeys, string $name): ?Family
    {
        $members = collect($memberKeys)
            ->filter(fn ($k) => isset($this->users[$k]))
            ->map(fn ($k) => $this->users[$k]->refresh())
            ->values();
        if ($members->isEmpty()) {
            return null;
        }

        $service = app(FamilyService::class);
        $familyId = $members->pluck('family_id')->filter()->first();
        $family = $familyId ? Family::find($familyId) : null;

        if ($family === null) {
            return $service->create($members, $name, Family::SOURCE_MANUAL, true);
        }

        foreach ($members as $member) {
            if ($member->family_id !== $family->id) {
                $service->addMember($family, $member);
            }
        }
        $family->update(['name' => $name, 'is_locked' => true]);
        $service->syncLegacySorg2($family);

        return $family;
    }

    private function demoAddToGroup(string $userKey, string $groupKey): void
    {
        if (! isset($this->users[$userKey], $this->groups[$groupKey])) {
            return;
        }
        $exists = DB::table('group_user')
            ->where('group_id', $this->groups[$groupKey])
            ->where('user_id', $this->users[$userKey]->id)
            ->exists();
        if (! $exists) {
            DB::table('group_user')->insert([
                'group_id' => $this->groups[$groupKey],
                'user_id' => $this->users[$userKey]->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // =========================================================================
    // Hort: Anwesenheit, Notizen, verspätete Abholungen, Anwesenheitsabfragen
    // =========================================================================
    private function createCareExtras(): void
    {
        // Hortkinder (wie in createChildren) mit üblicher Abholzeit je Wochentag
        $hortKinder = [
            'lena_mueller' => [1 => '13:30', 2 => '13:30', 3 => '15:00', 4 => '13:30', 5 => '12:15'],
            'emma_fischer' => [1 => '15:30', 2 => '15:30', 3 => '15:30', 4 => '15:30', 5 => '14:00'],
            'marie_koch' => [1 => '14:30', 2 => '16:00', 3 => '14:30', 4 => '16:00', 5 => '13:00'],
            'tim_bauer' => [1 => '15:00', 2 => '15:00', 3 => '12:15', 4 => '15:00', 5 => '12:15'],
        ];

        $schoolDays = $this->demoPastSchoolDays(10);

        // Besondere Tage (Index in $schoolDays, 0 = gestern bzw. letzter Schultag)
        $absent = ['emma_fischer' => [3], 'marie_koch' => [6]];
        $late = [
            'lena_mueller' => ['day' => 2, 'picked_up' => '16:50', 'status' => LatePickup::STATUS_OFFEN],
            'tim_bauer' => ['day' => 5, 'picked_up' => '16:45', 'status' => LatePickup::STATUS_BESTAETIGT],
        ];

        foreach ($hortKinder as $childKey => $pickupTimes) {
            if (! isset($this->children[$childKey])) {
                continue;
            }
            $child = $this->children[$childKey];

            foreach ($schoolDays as $index => $day) {
                $exists = ChildCheckIn::query()
                    ->where('child_id', $child->id)
                    ->whereDate('date', $day->toDateString())
                    ->exists();
                if ($exists) {
                    continue;
                }

                $isAbsent = in_array($index, $absent[$childKey] ?? [], true);
                $isLate = isset($late[$childKey]) && $late[$childKey]['day'] === $index;
                $pickup = $isLate ? $late[$childKey]['picked_up'] : ($pickupTimes[$day->dayOfWeek] ?? '15:00');
                // Ankunft im Hort nach Unterrichtsende, leicht variiert
                $arrival = $day->copy()->setTime(11, 40)->addMinutes(($index * 7 + $child->id * 3) % 50);

                $checkIn = new ChildCheckIn([
                    'child_id' => $child->id,
                    'date' => $day->toDateString(),
                    'should_be' => ! $isAbsent,
                    'checked_in' => ! $isAbsent,
                    'checked_out' => ! $isAbsent,
                    'checked_in_at' => $isAbsent ? null : $arrival,
                    'checked_out_at' => $isAbsent ? null : $day->copy()->setTimeFromTimeString($pickup),
                    'comment' => $isAbsent ? 'Abgemeldet (Arzttermin)' : null,
                ]);
                // Ohne Model-Events, sonst würden die Zeitstempel auf „jetzt“ gesetzt
                $checkIn->saveQuietly();

                if ($isLate) {
                    $this->demoLatePickup($child, $checkIn, $day, $pickupTimes[$day->dayOfWeek] ?? '15:00', $pickup, $late[$childKey]['status']);
                }
            }
        }

        // Notizen der Eltern
        $notices = [
            ['lena_mueller', 'mueller_vater', 0, 'Lena wird heute von Oma Ingrid abgeholt.'],
            ['tim_bauer', 'bauer', 1, 'Tim geht nach dem Hort direkt zum Schwimmtraining, er wird um 14:45 Uhr abgeholt.'],
            ['emma_fischer', 'fischer', 2, 'Emma darf am Donnerstag mit Lena Müller nach Hause gehen.'],
            ['marie_koch', 'koch', -3, 'Marie hat ihre Sportsachen im Hort vergessen.'],
        ];
        foreach ($notices as [$childKey, $userKey, $offset, $text]) {
            if (! isset($this->children[$childKey], $this->users[$userKey])) {
                continue;
            }
            ChildNotice::firstOrCreate(
                ['child_id' => $this->children[$childKey]->id, 'notice' => $text],
                ['date' => today()->addDays($offset), 'user_id' => $this->users[$userKey]->id]
            );
        }

        // Anwesenheitsabfrage des Horts (offene Tage = should_be null, Frist lock_at).
        // Wie SchickzeitenController::storeAttendanceQuery, aber ohne Benachrichtigung.
        $queryDays = $this->demoNextSchoolDays(2, today()->addWeek()->startOfWeek()->addDays(3)); // Do/Fr nächster Woche
        $lockAt = today()->addDays(5);
        foreach (['lena_mueller', 'emma_fischer', 'marie_koch', 'tim_bauer'] as $childKey) {
            if (! isset($this->children[$childKey])) {
                continue;
            }
            foreach ($queryDays as $day) {
                $exists = ChildCheckIn::query()
                    ->where('child_id', $this->children[$childKey]->id)
                    ->whereDate('date', $day->toDateString())
                    ->exists();
                if ($exists) {
                    continue;
                }
                ChildCheckIn::create([
                    'child_id' => $this->children[$childKey]->id,
                    'date' => $day->toDateString(),
                    'checked_in' => false,
                    'checked_out' => false,
                    // Familie Fischer hat bereits geantwortet, die anderen sind offen
                    'should_be' => $childKey === 'emma_fischer' ? false : null,
                    'lock_at' => $lockAt,
                ]);
            }
        }
    }

    private function demoLatePickup(Child $child, ChildCheckIn $checkIn, Carbon $day, string $expected, string $pickedUp, string $status): void
    {
        $exists = LatePickup::query()->where('child_id', $child->id)->whereDate('date', $day->toDateString())->exists();
        if ($exists) {
            return;
        }

        $expectedAt = $day->copy()->setTimeFromTimeString($expected);
        $pickedUpAt = $day->copy()->setTimeFromTimeString($pickedUp);
        $reviewed = $status !== LatePickup::STATUS_OFFEN;

        LatePickup::create([
            'child_id' => $child->id,
            'child_check_in_id' => $checkIn->id,
            'date' => $day->toDateString(),
            'weekday' => $day->dayOfWeek,
            'expected_time' => $expectedAt->format('H:i:s'),
            'picked_up_at' => $pickedUpAt,
            'delay_minutes' => (int) $expectedAt->diffInMinutes($pickedUpAt),
            'status' => $status,
            'reviewed_by' => $reviewed ? ($this->users['sekretariat']->id ?? null) : null,
            'reviewed_at' => $reviewed ? $day->copy()->addDay()->setTime(8, 15) : null,
            'review_comment' => $reviewed ? 'Mit den Eltern besprochen.' : null,
        ]);
    }

    /**
     * Die letzten $count Schultage vor heute (ohne Wochenenden und Ferien), neuester zuerst.
     *
     * @return list<Carbon>
     */
    private function demoPastSchoolDays(int $count): array
    {
        $days = [];
        $day = today()->subDay();
        for ($i = 0; count($days) < $count && $i < 60; $i++, $day = $day->copy()->subDay()) {
            if ($this->demoIsSchoolDay($day)) {
                $days[] = $day->copy();
            }
        }

        return $days;
    }

    /**
     * @return list<Carbon>
     */
    private function demoNextSchoolDays(int $count, Carbon $from): array
    {
        $days = [];
        $day = $from->copy();
        for ($i = 0; count($days) < $count && $i < 60; $i++, $day = $day->copy()->addDay()) {
            if ($this->demoIsSchoolDay($day)) {
                $days[] = $day->copy();
            }
        }

        return $days;
    }

    private function demoIsSchoolDay(Carbon $day): bool
    {
        if ($day->isWeekend()) {
            return false;
        }

        return ! Holiday::query()
            ->whereDate('start', '<=', $day->toDateString())
            ->whereDate('end', '>=', $day->toDateString())
            ->exists();
    }

    // =========================================================================
    // Meldepflichtige Erkrankungen (Aushang)
    // =========================================================================
    private function createActiveDiseases(): void
    {
        $cases = [
            // Veröffentlicht (erscheinen bei allen Eltern / in der App)
            ['disease' => 'Windpocken', 'user' => 'sekretariat', 'start' => today()->subDays(3),
                'comment' => 'Ein Fall in der Klasse 1a. Bitte achten Sie auf Hautausschlag und Fieber.', 'active' => true],
            ['disease' => 'Kopfläuse', 'user' => 'sekretariat', 'start' => today()->subDays(1),
                'comment' => 'Kopflausbefall in der Klasse 3b. Bitte kontrollieren Sie die Haare Ihres Kindes.', 'active' => true],
            // Noch nicht freigegeben (nur für die Verwaltung sichtbar)
            ['disease' => 'Scharlach', 'user' => 'wagner', 'start' => today(),
                'comment' => 'Meldung über eine Krankmeldung, noch nicht veröffentlicht.', 'active' => false],
        ];

        foreach ($cases as $case) {
            // Namen je nach Seeder leicht unterschiedlich (z. B. „Scharlach oder Angina …“)
            $disease = Disease::where('name', 'like', $case['disease'].'%')->orderBy('id')->first();
            if ($disease === null || ! isset($this->users[$case['user']])) {
                continue;
            }

            $exists = ActiveDisease::query()
                ->where('disease_id', $disease->id)
                ->where('user_id', $this->users[$case['user']]->id)
                ->whereDate('end', '>=', today())
                ->exists();
            if ($exists) {
                continue;
            }

            ActiveDisease::create([
                'disease_id' => $disease->id,
                'user_id' => $this->users[$case['user']]->id,
                'start' => $case['start'],
                'end' => $case['start']->copy()->addDays(max(7, (int) $disease->aushang_dauer)),
                'comment' => $case['comment'],
                'active' => $case['active'],
            ]);
        }

        Cache::forget('active_diseases');
    }
}

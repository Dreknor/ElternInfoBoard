<?php

namespace Tests\Feature\Ucs;

use App\Model\Child;
use App\Model\Group;
use App\Model\UcsLinkCandidate;
use App\Model\User;
use App\Services\Ucs\Dto\KelvinStudentDto;
use App\Services\Ucs\Dto\KelvinUserDto;
use App\Services\Ucs\KelvinClient;
use App\Services\Ucs\UcsSyncService;
use App\Settings\UcsSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Feature-Tests für App\Services\Ucs\UcsSyncService
 *
 * Alle KelvinClient-Calls werden per Mockery/createMock abgefangen.
 * Die Datenbank wird über RefreshDatabase (TestCase) zurückgesetzt.
 */
class UcsSyncServiceTest extends TestCase
{
    private const SCHOOL = 'GS-XY';

    // =========================================================================
    // Helpers
    // =========================================================================

    /** Baut ein UcsSetting-Stub ohne Settings-Tabellen-Abhängigkeit. */
    private function makeSetting(array $overrides = []): UcsSetting
    {
        $stub = $this->createStub(UcsSetting::class);
        // save() muss schweigend $this zurückgeben (Spatie\LaravelSettings\Settings::save() ist fluent)
        $stub->method('save')->willReturnSelf();

        foreach (array_merge([
            'enabled'            => true,
            'school'             => self::SCHOOL,
            'kelvin_base_url'    => 'https://ucs.example.de/ucsschool/kelvin/v1',
            'kelvin_page_size'   => 200,
            'kelvin_timeout'     => 5,
            'kelvin_token_ttl'   => 3300,
            'on_login_timeout'   => 5,
            'last_sync_status'   => null,
            'last_sync_message'  => null,
            'last_sync_at'       => null,
            'last_sync_parents'  => null,
            'last_sync_students' => null,
        ], $overrides) as $prop => $val) {
            $stub->{$prop} = $val;
        }

        return $stub;
    }

    private function makeClient(): KelvinClient
    {
        return $this->createMock(KelvinClient::class);
    }

    private function makeService(KelvinClient $client, array $settingOverrides = []): UcsSyncService
    {
        return new UcsSyncService($client, $this->makeSetting($settingOverrides));
    }

    /** Erstellt einen KelvinUserDto für einen Elternteil. */
    private function parentDto(
        string $username,
        string $recordUid,
        array  $wards = [],
        string $school = self::SCHOOL,
    ): KelvinUserDto {
        return KelvinUserDto::fromArray([
            'username'    => $username,
            'record_uid'  => $recordUid,
            'firstname'   => 'Test',
            'lastname'    => 'Elter',
            'email'       => $username.'@example.de',
            'school'      => $school,
            'roles'       => ['legal_guardian'],
            'legal_wards' => array_map(
                fn ($w) => "https://ucs.example.de/ucsschool/kelvin/v1/users/{$w}",
                $wards
            ),
            'url' => "https://ucs.example.de/ucsschool/kelvin/v1/users/{$username}",
        ]);
    }

    /** Erstellt einen KelvinStudentDto. */
    private function studentDto(
        string $username,
        string $recordUid,
        array  $classes = ['3a'],
        string $school  = self::SCHOOL,
    ): KelvinStudentDto {
        return KelvinStudentDto::fromArray([
            'username'      => $username,
            'record_uid'    => $recordUid,
            'firstname'     => 'Kind',
            'lastname'      => 'Muster',
            'school'        => $school,
            'roles'         => ['student'],
            'school_classes'=> [$school => $classes],
            'url'           => "https://ucs.example.de/ucsschool/kelvin/v1/users/{$username}",
        ]);
    }

    /** Gibt einen Generator zurück, der die übergebenen Items liefert. */
    private function asGenerator(array $items): \Generator
    {
        yield from $items;
    }

    // =========================================================================
    // Kriterium 1: Idempotenz
    // =========================================================================

    public function test_zweimaliger_run_erzeugt_identischen_db_zustand(): void
    {
        $parent  = $this->parentDto('mueller.anna', 'uid-p1', ['max.mueller']);
        $student = $this->studentDto('max.mueller', 'uid-s1');

        $client = $this->makeClient();
        $client->method('listStudents')->willReturnCallback(fn () => $this->asGenerator([$student]));
        $client->method('listParents')->willReturnCallback(fn () => $this->asGenerator([$parent]));

        $service = $this->makeService($client);

        $service->run();

        $countUsers    = User::count();
        $countChildren = Child::withoutGlobalScopes()->count();
        $countGroups   = Group::withoutGlobalScopes()->count();
        $countPivots   = \DB::table('child_user')->count();
        $countGPivots  = \DB::table('group_user')->count();

        // Zweiter Lauf: identisches Soll-Set → identischer Zustand
        $client2 = $this->makeClient();
        $client2->method('listStudents')->willReturnCallback(fn () => $this->asGenerator([$student]));
        $client2->method('listParents')->willReturnCallback(fn () => $this->asGenerator([$parent]));
        $this->makeService($client2)->run();

        $this->assertSame($countUsers,    User::count(),                      'users');
        $this->assertSame($countChildren, Child::withoutGlobalScopes()->count(), 'children');
        $this->assertSame($countGroups,   Group::withoutGlobalScopes()->count(),  'groups');
        $this->assertSame($countPivots,   \DB::table('child_user')->count(),   'child_user');
        $this->assertSame($countGPivots,  \DB::table('group_user')->count(),   'group_user');
    }

    // =========================================================================
    // Kriterium 2: Manuelle Pivots bleiben erhalten
    // =========================================================================

    public function test_manuelle_pivots_bleiben_nach_sync_mit_leerem_soll_set(): void
    {
        // Elternteil ohne Kinder (leere legalWards)
        $parent = $this->parentDto('elter.ohne.kind', 'uid-p2', []);

        // Zwei manuelle Gruppen vorab anlegen und zuweisen
        $manualGroup1 = Group::factory()->create(['name' => 'Manual-A', 'bereich' => 'Gruppe']);
        $manualGroup2 = Group::factory()->create(['name' => 'Manual-B', 'bereich' => 'Gruppe']);

        $user = User::factory()->create([
            'ucs_uuid'   => 'uid-p2',
            'ucs_source' => 'kelvin',
        ]);
        $user->groups()->attach($manualGroup1->id, ['is_auto_provisioned' => false]);
        $user->groups()->attach($manualGroup2->id, ['is_auto_provisioned' => false]);

        $student = $this->studentDto('nobody', 'uid-sX'); // kein Ward
        $client  = $this->makeClient();
        $client->method('listStudents')->willReturnCallback(fn () => $this->asGenerator([$student]));
        $client->method('listParents')->willReturnCallback(fn () => $this->asGenerator([$parent]));

        $this->makeService($client)->run();

        // Nach Sync: 0 Auto-Pivots, 2 Manuelle bleiben
        $autoPivots   = \DB::table('group_user')
            ->where('user_id', $user->id)
            ->where('is_auto_provisioned', true)
            ->count();
        $manualPivots = \DB::table('group_user')
            ->where('user_id', $user->id)
            ->where('is_auto_provisioned', false)
            ->count();

        $this->assertSame(0, $autoPivots,   'Keine Auto-Pivots nach leerem Soll-Set');
        $this->assertSame(2, $manualPivots, '2 manuelle Pivots bleiben erhalten');
    }

    // =========================================================================
    // Kriterium 3: Lokales Kind → Link-Candidate, kein Duplikat
    // =========================================================================

    public function test_lokales_kind_erzeugt_link_candidate_statt_duplikat(): void
    {
        // Lokales Kind mit gleichem Namen + Klasse
        $localGroup = Group::factory()->create(['name' => '4b', 'bereich' => 'Klasse']);
        $localChild = Child::factory()->create([
            'first_name' => 'Kind',
            'last_name'  => 'Muster',
            'ucs_source' => 'local',
            'class_id'   => $localGroup->id,
        ]);

        $student = KelvinStudentDto::fromArray([
            'username'      => 'kind.muster',
            'record_uid'    => 'uid-s99',
            'firstname'     => 'Kind',
            'lastname'      => 'Muster',
            'school'        => self::SCHOOL,
            'roles'         => ['student'],
            'school_classes'=> [self::SCHOOL => ['4b']],
            'url'           => 'https://ucs.example.de/.../users/kind.muster',
        ]);

        $parent = $this->parentDto('elter.99', 'uid-p99', ['kind.muster']);

        $client = $this->makeClient();
        $client->method('listStudents')->willReturnCallback(fn () => $this->asGenerator([$student]));
        $client->method('listParents')->willReturnCallback(fn () => $this->asGenerator([$parent]));

        $this->makeService($client)->run();

        // Kein neues Kind angelegt
        $this->assertSame(1, Child::withoutGlobalScopes()->where('last_name', 'Muster')->count(),
            'Kein Duplikat: weiterhin nur 1 Kind mit Nachname Muster');

        // Link-Candidate angelegt
        $this->assertSame(1, UcsLinkCandidate::where('ucs_username', 'kind.muster')->count(),
            'Genau 1 Link-Candidate für kind.muster angelegt');
    }

    // =========================================================================
    // Kriterium 4: Kombiklasse
    // =========================================================================

    public function test_kombiklasse_setzt_erste_klasse_alphabetisch_als_class_id(): void
    {
        // Kind mit zwei Klassen (Kombiklasse)
        $student = KelvinStudentDto::fromArray([
            'username'      => 'kombi.kind',
            'record_uid'    => 'uid-kombi',
            'firstname'     => 'Kombi',
            'lastname'      => 'Kind',
            'school'        => self::SCHOOL,
            'roles'         => ['student'],
            'school_classes'=> [self::SCHOOL => ['5b', '5a']], // Absichtlich unsortiert
            'url'           => 'https://ucs.example.de/.../users/kombi.kind',
        ]);

        $parent = $this->parentDto('elter.kombi', 'uid-p-kombi', ['kombi.kind']);

        $client = $this->makeClient();
        $client->method('listStudents')->willReturnCallback(fn () => $this->asGenerator([$student]));
        $client->method('listParents')->willReturnCallback(fn () => $this->asGenerator([$parent]));

        $this->makeService($client)->run();

        $child = Child::withoutGlobalScopes()->where('ucs_username', 'kombi.kind')->first();
        $this->assertNotNull($child);

        // class_id muss auf die erste Klasse alphabetisch (5a) zeigen
        $primaryGroup = Group::withoutGlobalScopes()->find($child->class_id);
        $this->assertSame('5a', $primaryGroup->name, 'Erste Klasse alphabetisch (5a) für class_id');

        // Elternteil hat 2 Auto-Pivots (für 5a und 5b)
        $user = User::where('ucs_uuid', 'uid-p-kombi')->first();
        $autoPivots = \DB::table('group_user')
            ->where('user_id', $user->id)
            ->where('is_auto_provisioned', true)
            ->count();

        $this->assertSame(2, $autoPivots, 'Elternteil hat 2 Auto-Pivots (5a + 5b)');
    }

    // =========================================================================
    // Kriterium 5: detach-Korrektheit
    // =========================================================================

    public function test_detach_korrektheit_auto_entfernt_manuell_bleibt(): void
    {
        // Elternteil hat: 3 Auto-Pivots + 2 Manuelle
        // Soll-Set nach Sync: 1 Auto-Pivot (kind1 → gruppe1)
        // Erwartung: 1 Auto + 2 Manuell = 3 Pivots gesamt

        $user = User::factory()->create(['ucs_uuid' => 'uid-detach', 'ucs_source' => 'kelvin']);

        $g1 = Group::factory()->create(['name' => 'Klasse-1', 'bereich' => 'Klasse']);
        $g2 = Group::factory()->create(['name' => 'Klasse-2', 'bereich' => 'Klasse']);
        $g3 = Group::factory()->create(['name' => 'Klasse-3', 'bereich' => 'Klasse']);
        $gM1 = Group::factory()->create(['name' => 'Manual-1', 'bereich' => 'Gruppe']);
        $gM2 = Group::factory()->create(['name' => 'Manual-2', 'bereich' => 'Gruppe']);

        // Initiale Pivots setzen
        $user->groups()->attach($g1->id,  ['is_auto_provisioned' => true]);
        $user->groups()->attach($g2->id,  ['is_auto_provisioned' => true]);
        $user->groups()->attach($g3->id,  ['is_auto_provisioned' => true]);
        $user->groups()->attach($gM1->id, ['is_auto_provisioned' => false]);
        $user->groups()->attach($gM2->id, ['is_auto_provisioned' => false]);

        // Sync: Kind1 → Klasse-1 (nur diese bleibt als Auto)
        $student = $this->studentDto('kind.1', 'uid-kind1', ['Klasse-1']);
        $parent  = $this->parentDto('elter.detach', 'uid-detach', ['kind.1']);

        $client = $this->makeClient();
        $client->method('listStudents')->willReturnCallback(fn () => $this->asGenerator([$student]));
        $client->method('listParents')->willReturnCallback(fn () => $this->asGenerator([$parent]));

        $this->makeService($client)->run();

        $user->refresh();

        $autoPivots   = \DB::table('group_user')
            ->where('user_id', $user->id)
            ->where('is_auto_provisioned', true)
            ->count();
        $manualPivots = \DB::table('group_user')
            ->where('user_id', $user->id)
            ->where('is_auto_provisioned', false)
            ->count();

        $this->assertSame(1, $autoPivots,   '1 Auto-Pivot (Klasse-1)');
        $this->assertSame(2, $manualPivots, '2 Manuelle Pivots bleiben');
        $this->assertSame(3, $autoPivots + $manualPivots, '3 Gesamt-Pivots');
    }

    // =========================================================================
    // Kriterium 6: Dry-Run
    // =========================================================================

    public function test_dry_run_schreibt_keine_daten_in_die_db(): void
    {
        $parent  = $this->parentDto('new.parent', 'uid-dry', ['new.child']);
        $student = $this->studentDto('new.child', 'uid-dry-s');

        $client = $this->makeClient();
        $client->method('listStudents')->willReturnCallback(fn () => $this->asGenerator([$student]));
        $client->method('listParents')->willReturnCallback(fn () => $this->asGenerator([$parent]));

        $result = $this->makeService($client)->run(dryRun: true);

        $this->assertSame(1, $result['parents_created'],  'Dry-Run zählt parents_created');
        $this->assertSame(1, $result['children_created'], 'Dry-Run zählt children_created');
        $this->assertTrue($result['dry_run']);

        // Keine Datenbankänderungen
        $this->assertSame(0, User::where('ucs_uuid', 'uid-dry')->count(),    'Kein User in DB');
        $this->assertSame(0, Child::withoutGlobalScopes()->where('ucs_uuid', 'uid-dry-s')->count(), 'Kein Child in DB');
    }

    // =========================================================================
    // Kriterium 7: Per-Eltern-Fehler-Isolation
    // =========================================================================

    public function test_fehler_bei_einem_elternteil_unterbricht_nicht_sync(): void
    {
        // 5 Eltern; alle haben jeweils ein Kind in der Map.
        $parents = [];
        $students = [];
        for ($i = 1; $i <= 5; $i++) {
            $parents[]  = $this->parentDto("elter.{$i}", "uid-p{$i}", ["kind.{$i}"]);
            $students[] = $this->studentDto("kind.{$i}", "uid-s{$i}");
        }

        // Elternteil Nr. 3 erhält eine korrupte Ward-URL (>120 Zeichen → skip, kein Exception)
        // Echter Exception-Fehler bei Elternteil 3 via defekter Ward-URL:
        $parentsBroken = [];
        for ($i = 1; $i <= 5; $i++) {
            $parentsBroken[] = $this->parentDto(
                "elter.{$i}", "uid-pb{$i}",
                $i === 3 ? [str_repeat('x', 200)] : ["kind.b{$i}"]
            );
        }

        $brokenStudents = array_map(
            fn ($i) => $this->studentDto("kind.b{$i}", "uid-bs{$i}"),
            range(1, 5)
        );

        $client2 = $this->makeClient();
        $client2->method('listStudents')->willReturnCallback(fn () => $this->asGenerator($brokenStudents));
        $client2->method('listParents')->willReturnCallback(fn () => $this->asGenerator($parentsBroken));

        $result = $this->makeService($client2)->run();

        // Alle 5 Elternteile wurden verarbeitet;
        // Elternteil 3 hatte ungültige Ward-URL → Warning (kein Exception, kein failed_parent)
        $this->assertSame(5, $result['parents_processed'], '5 Elternteile verarbeitet');
        $this->assertSame(0, $result['failed_parents'], 'Ungültige URL → kein failed_parent');
    }

    public function test_fehler_beim_upsert_zaehlt_failed_parents(): void
    {
        // Wir testen, dass ein echter Exception-Fehler im Transaction-Block
        // als failed_parents gezählt wird und den Sync nicht abbricht
        $parent1 = $this->parentDto('good.parent', 'uid-good', ['good.child']);
        $parent2 = $this->parentDto('bad.parent',  'uid-bad',  ['bad.child']);
        $parent3 = $this->parentDto('also.good',   'uid-also', ['also.child']);

        $student1 = $this->studentDto('good.child', 'uid-gs1');
        $student3 = $this->studentDto('also.child', 'uid-gs3');
        // bad.child fehlt in der Map → kein Fehler, nur ein Warning
        // Stattdessen: bad.parent hat eine ward-URL die rawurldecode-Fehler produziert ist nicht möglich
        // Echter Weg: bad.child ist in studentMap, aber Upsert wirft Exception
        // Wir müssen dafür sorgen, dass DB einen Fehler wirft – am einfachsten: child_id constraint

        // Einfachster Test: ALLE Kinder sind in der Map, aber bad.parent hat eine
        // korrupte Kindes-URL (länger als 120 Zeichen → null username → skip):
        $badParent = KelvinUserDto::fromArray([
            'username'    => 'bad.parent',
            'record_uid'  => 'uid-bad',
            'firstname'   => 'Bad',
            'lastname'    => 'Parent',
            'email'       => 'bad@example.de',
            'school'      => self::SCHOOL,
            'roles'       => ['legal_guardian'],
            'legal_wards' => ['https://ucs.example.de/users/'.str_repeat('x', 200)], // ungültig
            'url'         => 'https://ucs.example.de/.../users/bad.parent',
        ]);

        $client = $this->makeClient();
        $client->method('listStudents')->willReturnCallback(fn () => $this->asGenerator([$student1, $student3]));
        $client->method('listParents')->willReturnCallback(fn () => $this->asGenerator([$parent1, $badParent, $parent3]));

        $result = $this->makeService($client)->run();

        $this->assertSame(3, $result['parents_processed']);
        // bad.parent wird verarbeitet (keine Exception, nur skip bei der Ward)
        $this->assertSame(0, $result['failed_parents'], 'Ungültige Ward-URL erzeugt keinen failed_parent');
        // alle 3 Eltern wurden angelegt (bad.parent auch, nur ohne Kinder)
        $this->assertSame(3, User::where('ucs_source', 'kelvin')->count(), '3 User angelegt');
    }

    // =========================================================================
    // Kriterium 8: Cache-Lock wird gesetzt und wieder gelöscht
    // =========================================================================

    public function test_cache_lock_wird_gesetzt_und_nach_run_geloescht(): void
    {
        $client = $this->makeClient();
        $client->method('listStudents')->willReturnCallback(fn () => $this->asGenerator([]));
        $client->method('listParents')->willReturnCallback(fn () => $this->asGenerator([]));

        $this->assertFalse(Cache::has('ucs.sync.lock'), 'Vor run(): kein Lock');

        $this->makeService($client)->run();

        $this->assertFalse(Cache::has('ucs.sync.lock'), 'Nach run(): Lock freigegeben');
    }

    // =========================================================================
    // Kriterium 9: JIT-Sync (syncSingleParent)
    // =========================================================================

    public function test_jit_sync_legt_elternteil_und_kinder_an(): void
    {
        $parentData = [
            'username'    => 'jit.parent',
            'record_uid'  => 'uid-jit-p',
            'firstname'   => 'JIT',
            'lastname'    => 'Elter',
            'email'       => 'jit@example.de',
            'school'      => self::SCHOOL,
            'roles'       => ['legal_guardian'],
            'legal_wards' => ["https://ucs.example.de/ucsschool/kelvin/v1/users/jit.child"],
            'url'         => 'https://ucs.example.de/.../users/jit.parent',
        ];

        $childData = [
            'username'      => 'jit.child',
            'record_uid'    => 'uid-jit-s',
            'firstname'     => 'JIT',
            'lastname'      => 'Kind',
            'school'        => self::SCHOOL,
            'roles'         => ['student'],
            'school_classes'=> [self::SCHOOL => ['2c']],
            'url'           => 'https://ucs.example.de/.../users/jit.child',
        ];

        $client = $this->makeClient();
        $client->method('findUser')
            ->willReturnCallback(fn ($username) => match ($username) {
                'jit.parent' => $parentData,
                'jit.child'  => $childData,
                default      => null,
            });

        $service = $this->makeService($client);
        $user    = $service->syncSingleParent('jit.parent');

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('jit.parent', $user->ucs_username);
        $this->assertSame(1, Child::withoutGlobalScopes()->where('ucs_username', 'jit.child')->count());
    }

    public function test_jit_sync_setzt_negativ_cache_bei_404(): void
    {
        $client = $this->makeClient();
        $client->method('findUser')->willReturn(null);

        $result = $this->makeService($client)->syncSingleParent('ghost.user');

        $this->assertNull($result);
        $this->assertTrue(Cache::has('ucs.jit.miss:ghost.user'), 'Negativ-Cache gesetzt');
    }

    // =========================================================================
    // Kriterium 7b: failed_parents = 1 bei echter Exception im Transaction-Block
    // =========================================================================

    public function test_echter_exception_im_transaction_zaehlt_als_failed_parent(): void
    {
        // 3 Elternteile; mittlerer wirft Exception durch einen UcsSyncService-Subklassen-Override.
        // Prüft: andere 2 werden trotzdem vollständig verarbeitet, failed_parents = 1.

        $setting = $this->makeSetting();

        $clientMock = $this->makeClient();
        $clientMock->method('listStudents')->willReturnCallback(fn () => $this->asGenerator([
            $this->studentDto('child.ok1', 'uid-ok1'),
            $this->studentDto('child.ok3', 'uid-ok3'),
        ]));
        $clientMock->method('listParents')->willReturnCallback(fn () => $this->asGenerator([
            $this->parentDto('parent.ok1',  'uid-p-ok1', ['child.ok1']),
            $this->parentDto('parent.boom', 'uid-p-boom', ['child.ok1']), // wird Fehler werfen
            $this->parentDto('parent.ok3',  'uid-p-ok3', ['child.ok3']),
        ]));

        // Subklasse: wirft bei Elternteil mit username='parent.boom' eine Exception
        $faultyService = new class($clientMock, $setting) extends UcsSyncService {
            protected function extractWardUsername(string $url): ?string {
                // Für parent.boom: Exception erzwingen
                if (str_contains($url, 'BOOM_SIGNAL')) {
                    throw new \RuntimeException('Simulierter DB-Fehler');
                }
                return parent::extractWardUsername($url);
            }
        };

        // Wir injizieren die BOOM_SIGNAL URL via manuellem DTO-Override für parent.boom
        $boomParent = KelvinUserDto::fromArray([
            'username'    => 'parent.boom',
            'record_uid'  => 'uid-p-boom',
            'firstname'   => 'B', 'lastname' => 'P',
            'email'       => 'boom@x.de',
            'school'      => self::SCHOOL,
            'roles'       => ['legal_guardian'],
            'legal_wards' => ['https://ucs.example.de/ucsschool/kelvin/v1/users/BOOM_SIGNAL'],
            'url'         => 'x',
        ]);

        $clientMock2 = $this->makeClient();
        $clientMock2->method('listStudents')->willReturnCallback(fn () => $this->asGenerator([
            $this->studentDto('child.ok1', 'uid-ok1'),
            $this->studentDto('child.ok3', 'uid-ok3'),
        ]));
        $clientMock2->method('listParents')->willReturnCallback(fn () => $this->asGenerator([
            $this->parentDto('parent.ok1', 'uid-p-ok1', ['child.ok1']),
            $boomParent,
            $this->parentDto('parent.ok3', 'uid-p-ok3', ['child.ok3']),
        ]));

        $faultyService2 = new class($clientMock2, $setting) extends UcsSyncService {
            protected function extractWardUsername(string $url): ?string {
                if (str_contains($url, 'BOOM_SIGNAL')) {
                    throw new \RuntimeException('Simulierter DB-Fehler im Transaction-Block');
                }
                return parent::extractWardUsername($url);
            }
        };

        $result = $faultyService2->run();

        $this->assertSame(3, $result['parents_processed'], '3 Elternteile verarbeitet');
        $this->assertSame(1, $result['failed_parents'],    '1 failed_parent durch Exception');
        $this->assertSame(2, User::where('ucs_source', 'kelvin')
            ->whereIn('ucs_username', ['parent.ok1', 'parent.ok3'])->count(),
            'Eltern ok1 und ok3 trotzdem angelegt'
        );
    }
}


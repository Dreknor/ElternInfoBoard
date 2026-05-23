<?php

namespace Tests\Feature\Performance;

use App\Services\Ucs\Dto\KelvinStudentDto;
use App\Services\Ucs\Dto\KelvinUserDto;
use App\Services\Ucs\KelvinClient;
use App\Services\Ucs\UcsSyncService;
use App\Settings\UcsSetting;
use Tests\TestCase;

/**
 * Last-Test für UcsSyncService::run() mit 2 000 Eltern + 4 000 Kindern.
 *
 * Läuft nur im separaten Performance-Testsuite (phpunit --testsuite Performance).
 * Assert: Laufzeit < 60 Sekunden auf einem Standard-CI-Worker.
 *
 * @group performance
 */
class UcsBulkSyncPerformanceTest extends TestCase
{
    /** Anzahl simulierter Elternteile. */
    private const PARENT_COUNT = 2_000;

    /** Anzahl simulierter Kinder. */
    private const CHILD_COUNT = 4_000;

    /** Maximale Laufzeit in Sekunden. */
    private const MAX_SECONDS = 60;

    private const SCHOOL = 'GS-PERF';

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeSetting(): UcsSetting
    {
        $stub = $this->createStub(UcsSetting::class);
        $stub->method('save')->willReturnSelf();

        foreach ([
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
        ] as $prop => $val) {
            $stub->{$prop} = $val;
        }

        return $stub;
    }

    /**
     * Generiert In-Memory-Fixture-Daten für CHILD_COUNT Schüler.
     * Jeder Schüler hat eine eindeutige ID und ist einer von 20 Klassen zugeordnet.
     *
     * @return KelvinStudentDto[]
     */
    private function generateStudents(): array
    {
        $classes = ['1a', '1b', '2a', '2b', '3a', '3b', '4a', '4b', '5a', '5b',
                    '6a', '6b', '7a', '7b', '8a', '8b', '9a', '9b', '10a', '10b'];

        $students = [];
        for ($i = 1; $i <= self::CHILD_COUNT; $i++) {
            $class    = $classes[$i % count($classes)];
            $students[] = KelvinStudentDto::fromArray([
                'username'      => "student.{$i}",
                'record_uid'    => sprintf('uid-s-%06d', $i),
                'firstname'     => 'Kind',
                'lastname'      => "Nr{$i}",
                'school'        => self::SCHOOL,
                'roles'         => ['student'],
                'school_classes'=> [self::SCHOOL => [$class]],
                'url'           => "https://ucs.example.de/.../users/student.{$i}",
            ]);
        }

        return $students;
    }

    /**
     * Generiert In-Memory-Fixture-Daten für PARENT_COUNT Elternteile.
     * Jedem Elternteil werden 2 Kinder (via ward-URLs) zugeordnet.
     *
     * @return KelvinUserDto[]
     */
    private function generateParents(): array
    {
        $parents = [];
        for ($i = 1; $i <= self::PARENT_COUNT; $i++) {
            // Jede Elternteil → 2 Kinder (sequenzielle Zuordnung)
            $ward1 = (($i * 2) - 1);
            $ward2 = ($i * 2);

            $wards = [];
            if ($ward1 <= self::CHILD_COUNT) {
                $wards[] = "https://ucs.example.de/ucsschool/kelvin/v1/users/student.{$ward1}";
            }
            if ($ward2 <= self::CHILD_COUNT) {
                $wards[] = "https://ucs.example.de/ucsschool/kelvin/v1/users/student.{$ward2}";
            }

            $parents[] = KelvinUserDto::fromArray([
                'username'    => "parent.{$i}",
                'record_uid'  => sprintf('uid-p-%06d', $i),
                'firstname'   => 'Elter',
                'lastname'    => "Nr{$i}",
                'email'       => "parent{$i}@example.de",
                'school'      => self::SCHOOL,
                'roles'       => ['legal_guardian'],
                'legal_wards' => $wards,
                'url'         => "https://ucs.example.de/.../users/parent.{$i}",
            ]);
        }

        return $parents;
    }

    // =========================================================================
    // Last-Test
    // =========================================================================

    /**
     * @group performance
     */
    public function test_bulk_sync_2000_eltern_4000_kinder_unter_60_sekunden(): void
    {
        $students = $this->generateStudents();
        $parents  = $this->generateParents();

        $asGenerator = static function (array $items): \Generator {
            yield from $items;
        };

        $clientMock = $this->createMock(KelvinClient::class);
        $clientMock->method('listStudents')
            ->willReturnCallback(static fn () => $asGenerator($students));
        $clientMock->method('listParents')
            ->willReturnCallback(static fn () => $asGenerator($parents));

        $service = new UcsSyncService($clientMock, $this->makeSetting());

        $start  = microtime(true);
        $result = $service->run();
        $elapsed = microtime(true) - $start;

        // Ergebnis-Plausibilitäts-Checks
        $this->assertSame(self::PARENT_COUNT, $result['parents_processed'],
            'Alle '.self::PARENT_COUNT.' Elternteile verarbeitet');

        $this->assertSame(0, $result['failed_parents'],
            'Keine fehlgeschlagenen Elternteile');

        // Performance-Assertion: < 60 Sekunden
        $this->assertLessThan(
            self::MAX_SECONDS,
            $elapsed,
            sprintf(
                'Bulk-Sync dauerte %.2f s, Limit ist %d s.',
                $elapsed,
                self::MAX_SECONDS
            )
        );

        // Ausgabe für CI-Protokoll
        fwrite(STDOUT, sprintf(
            "\n  [Performance] Bulk-Sync %d Eltern / %d Kinder: %.2f s (Limit: %d s)\n",
            self::PARENT_COUNT,
            self::CHILD_COUNT,
            $elapsed,
            self::MAX_SECONDS
        ));
    }
}


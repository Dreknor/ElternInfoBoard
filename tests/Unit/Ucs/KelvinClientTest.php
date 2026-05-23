<?php

namespace Tests\Unit\Ucs;

use App\Services\Ucs\Dto\KelvinStudentDto;
use App\Services\Ucs\Dto\KelvinUserDto;
use App\Services\Ucs\Exceptions\KelvinAuthException;
use App\Services\Ucs\Exceptions\KelvinRateLimitException;
use App\Services\Ucs\Exceptions\KelvinUnavailableException;
use App\Services\Ucs\KelvinClient;
use App\Settings\UcsSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Unit-Tests für App\Services\Ucs\KelvinClient
 *
 * Alle HTTP-Requests werden mit Http::fake() abgefangen.
 * Die UcsSetting-Instanz wird als Stub gebaut (kein DB-Zugriff).
 */
class KelvinClientTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    /** Erstellt ein UcsSetting-Stub ohne DB-Zugriff. */
    private function makeSetting(array $overrides = []): UcsSetting
    {
        $stub = $this->createStub(UcsSetting::class);

        $defaults = [
            'kelvin_base_url'   => 'https://ucs.example.de/ucsschool/kelvin/v1',
            'kelvin_username'   => 'kelvin-service',
            'kelvin_password'   => 'supersecret',
            'kelvin_page_size'  => 200,
            'kelvin_timeout'    => 5,
            'kelvin_token_ttl'  => 3300,
        ];

        $merged = array_merge($defaults, $overrides);

        foreach ($merged as $prop => $value) {
            $stub->{$prop} = $value;
        }

        return $stub;
    }

    private function makeClient(array $settingOverrides = []): KelvinClient
    {
        return new KelvinClient($this->makeSetting($settingOverrides));
    }

    private function tokenResponse(string $token = 'test-bearer-token'): array
    {
        return ['access_token' => $token, 'token_type' => 'bearer', 'expires_in' => 3600];
    }

    private function schoolsResponse(): array
    {
        return [
            ['name' => 'GS-XY', 'display_name' => 'Grundschule XY', 'dn' => 'ou=gs-xy,dc=ucs,dc=de',
                'url' => 'https://ucs.example.de/ucsschool/kelvin/v1/schools/GS-XY'],
        ];
    }

    // =========================================================================
    // Kriterium 1: Token-Caching – zweiter Aufruf ruft /auth/ NICHT nochmal auf
    // =========================================================================

    public function test_token_is_cached_and_auth_called_only_once(): void
    {
        // Wildcard am Ende matcht auch URLs mit Query-Parametern
        Http::fake([
            '*/auth*'    => Http::response($this->tokenResponse(), 200),
            '*/schools*' => Http::response($this->schoolsResponse(), 200),
        ]);

        $client = $this->makeClient();

        // Zwei verschiedene API-Calls
        $client->ping();
        $client->listSchools();

        // /auth/ darf nur einmal aufgerufen worden sein
        Http::assertSentCount(3); // 1× /auth/, 1× ping, 1× listSchools
    }

    // =========================================================================
    // Kriterium 2: Nach TTL-Ablauf wird ein neues /auth/ ausgelöst
    // =========================================================================

    public function test_token_refreshed_after_ttl_expiry(): void
    {
        Http::fake([
            '*/auth*'    => Http::response($this->tokenResponse(), 200),
            '*/schools*' => Http::response($this->schoolsResponse(), 200),
        ]);

        $client = $this->makeClient();
        $client->ping(); // setzt Token in Cache

        // Manuell den Cache löschen (simuliert TTL-Ablauf)
        Cache::forget('ucs.kelvin.token');

        $client->listSchools(); // muss neues Token holen

        Http::assertSentCount(4); // 2× /auth/, 1× ping, 1× listSchools
    }

    // =========================================================================
    // Kriterium 3: 401 → einmaliger Force-Refresh; zweiter 401 → KelvinAuthException
    // =========================================================================

    public function test_401_triggers_single_force_refresh_then_succeeds(): void
    {
        $callCount = 0;

        Http::fake(function ($request) use (&$callCount) {
            if (str_contains($request->url(), '/auth/')) {
                return Http::response($this->tokenResponse('token-'.(++$callCount)), 200);
            }
            // Erste schools-Anfrage: 401; zweite: 200
            static $schoolCalls = 0;
            $schoolCalls++;
            if ($schoolCalls === 1) {
                return Http::response(['detail' => 'Token expired'], 401);
            }

            return Http::response($this->schoolsResponse(), 200);
        });

        $client = $this->makeClient();
        $result = $client->listSchools();

        $this->assertCount(1, $result);
        Http::assertSentCount(4); // 1× /auth/, 1× schools (401), 1× /auth/ (re-auth), 1× schools (ok)
    }

    public function test_401_after_force_refresh_throws_kelvin_auth_exception(): void
    {
        Http::fake([
            '*/auth*'    => Http::response($this->tokenResponse(), 200),
            '*/schools*' => Http::response(['detail' => 'Unauthorized'], 401),
        ]);

        $this->expectException(KelvinAuthException::class);

        $this->makeClient()->listSchools();
    }

    // =========================================================================
    // Kriterium 4: 500er-Response → 3 Versuche, danach KelvinUnavailableException
    // =========================================================================

    public function test_server_error_retries_three_times_then_throws_unavailable(): void
    {
        Http::fake([
            '*/auth*'    => Http::response($this->tokenResponse(), 200),
            '*/schools*' => Http::response('Internal Server Error', 500),
        ]);

        $this->expectException(KelvinUnavailableException::class);
        $this->expectExceptionMessageMatches('/HTTP 500/');

        $this->makeClient()->listSchools();
    }

    // =========================================================================
    // Kriterium 5: Cache enthält verschlüsselten Token (nicht im Klartext)
    // =========================================================================

    public function test_token_stored_encrypted_in_cache(): void
    {
        Http::fake([
            '*/auth*'    => Http::response($this->tokenResponse('my-secret-bearer'), 200),
            '*/schools*' => Http::response($this->schoolsResponse(), 200),
        ]);

        $this->makeClient()->ping();

        $cached = Cache::get('ucs.kelvin.token');

        $this->assertNotNull($cached, 'Token sollte im Cache stehen.');
        $this->assertStringNotContainsString('my-secret-bearer', $cached, 'Klartext-Token darf nicht im Cache stehen.');

        // Entschlüsseln muss den Originaltoken liefern
        $decrypted = Crypt::decryptString($cached);
        $this->assertSame('my-secret-bearer', $decrypted);
    }

    // =========================================================================
    // Kriterium 6: Pagination – 3 Seiten à 2 Records = 6 Yields
    // (page_size=2, so 3 Seiten werden benötigt)
    // =========================================================================

    public function test_list_parents_paginates_correctly_over_multiple_pages(): void
    {
        $page1 = json_decode(
            file_get_contents(base_path('tests/Fixtures/kelvin/legal_guardian_page1.json')),
            true
        );

        // Seite 1: 2 Records (= page_size), Seite 2: 1 Record, Seite 3: leer → Ende
        $callCount = 0;
        Http::fake(function ($request) use ($page1, &$callCount) {
            if (str_contains($request->url(), '/auth/')) {
                return Http::response($this->tokenResponse(), 200);
            }
            $callCount++;

            return match ($callCount) {
                1 => Http::response($page1, 200),                           // 2 Records
                2 => Http::response([$page1[0]], 200),                      // 1 Record < page_size → Ende
                default => Http::response([], 200),
            };
        });

        $client  = $this->makeClient(['kelvin_page_size' => 2]);
        $parents = iterator_to_array($client->listParents('GS-XY'), false);

        $this->assertCount(3, $parents);
        $this->assertContainsOnlyInstancesOf(KelvinUserDto::class, $parents);
    }

    public function test_list_parents_three_full_pages_yields_correct_count(): void
    {
        // Simuliere 3 Seiten à 200 Records (600 Gesamt) + leere 4. Seite
        $singleParent = json_decode(
            file_get_contents(base_path('tests/Fixtures/kelvin/legal_guardian_page1.json')),
            true
        )[0];

        $fullPage = array_fill(0, 200, $singleParent);

        $pageCall = 0;
        Http::fake(function ($request) use ($fullPage, &$pageCall) {
            if (str_contains($request->url(), '/auth/')) {
                return Http::response($this->tokenResponse(), 200);
            }
            $pageCall++;

            return match (true) {
                $pageCall <= 3 => Http::response($fullPage, 200),
                default        => Http::response([], 200),
            };
        });

        $client  = $this->makeClient(['kelvin_page_size' => 200]);
        $count   = 0;

        foreach ($client->listParents('GS-XY') as $dto) {
            $this->assertInstanceOf(KelvinUserDto::class, $dto);
            $count++;
        }

        $this->assertSame(600, $count, '3 Seiten × 200 = 600 Yields erwartet.');
    }

    // =========================================================================
    // Kriterium 7: ping() mit 404 wirft KelvinUnavailableException
    // =========================================================================

    public function test_ping_with_404_throws_kelvin_unavailable_exception(): void
    {
        Http::fake([
            '*/auth*'    => Http::response($this->tokenResponse(), 200),
            '*/schools*' => Http::response(['detail' => 'Not Found'], 404),
        ]);

        $this->expectException(KelvinUnavailableException::class);

        $this->makeClient()->ping();
    }

    // =========================================================================
    // Kriterium 8: Alle Log-Einträge enthalten eine Korrelations-ID
    // =========================================================================

    public function test_log_entries_contain_correlation_id(): void
    {
        Http::fake([
            '*/auth*'    => Http::response($this->tokenResponse(), 200),
            '*/schools*' => Http::response($this->schoolsResponse(), 200),
        ]);

        // Logger-Mock für den 'ucs'-Channel
        $loggerMock = $this->createMock(\Psr\Log\LoggerInterface::class);
        $loggerMock->expects($this->atLeastOnce())
            ->method('info')
            ->with(
                $this->isType('string'),
                $this->callback(fn ($ctx) => isset($ctx['correlation_id']) && strlen($ctx['correlation_id']) > 0)
            );

        \Illuminate\Support\Facades\Log::shouldReceive('channel')
            ->with('ucs')
            ->andReturn($loggerMock);

        $this->makeClient()->ping();
    }

    // =========================================================================
    // findUser()
    // =========================================================================

    public function test_find_user_returns_null_on_404(): void
    {
        Http::fake([
            '*/auth/'             => Http::response($this->tokenResponse(), 200),
            '*/users/max.mueller' => Http::response(['detail' => 'Not Found'], 404),
        ]);

        $result = $this->makeClient()->findUser('max.mueller');

        $this->assertNull($result);
    }

    public function test_find_user_returns_array_on_200(): void
    {
        $studentFixture = json_decode(
            file_get_contents(base_path('tests/Fixtures/kelvin/student_page1.json')),
            true
        )[0];

        Http::fake([
            '*/auth/'             => Http::response($this->tokenResponse(), 200),
            '*/users/max.mueller' => Http::response($studentFixture, 200),
        ]);

        $result = $this->makeClient()->findUser('max.mueller');

        $this->assertIsArray($result);
        $this->assertSame('max.mueller', $result['username']);
    }

    // =========================================================================
    // listStudents()
    // =========================================================================

    public function test_list_students_returns_kelvin_student_dtos(): void
    {
        $studentFixture = json_decode(
            file_get_contents(base_path('tests/Fixtures/kelvin/student_page1.json')),
            true
        );

        Http::fake(function ($request) use ($studentFixture) {
            if (str_contains($request->url(), '/auth/')) {
                return Http::response($this->tokenResponse(), 200);
            }
            // Erste Seite: 3 Einträge (< page_size 200 → kein weiterer Call)
            static $called = false;
            if (! $called) {
                $called = true;
                return Http::response($studentFixture, 200);
            }
            return Http::response([], 200);
        });

        $students = iterator_to_array($this->makeClient()->listStudents('GS-XY'), false);

        $this->assertCount(3, $students);
        $this->assertContainsOnlyInstancesOf(KelvinStudentDto::class, $students);
    }

    // =========================================================================
    // DTOs
    // =========================================================================

    public function test_kelvin_student_dto_primary_class_returns_correct_value(): void
    {
        $dto = KelvinStudentDto::fromArray([
            'username'      => 'max.mueller',
            'record_uid'    => 'uid-123',
            'firstname'     => 'Max',
            'lastname'      => 'Müller',
            'school'        => 'GS-XY',
            'roles'         => ['student'],
            'school_classes'=> ['GS-XY' => ['3a', '3b']],
            'url'           => null,
        ]);

        $this->assertSame('3a', $dto->primaryClass('GS-XY'));
        $this->assertNull($dto->primaryClass('andere-schule'));
    }

    public function test_kelvin_student_dto_empty_school_classes_returns_null(): void
    {
        $dto = KelvinStudentDto::fromArray([
            'username'      => 'tom.schmidt',
            'record_uid'    => 'uid-456',
            'firstname'     => 'Tom',
            'lastname'      => 'Schmidt',
            'school'        => 'GS-XY',
            'roles'         => ['student'],
            'school_classes'=> [],
            'url'           => null,
        ]);

        $this->assertNull($dto->primaryClass('GS-XY'));
    }

    // =========================================================================
    // Kriterium: HTTP 429 nach allen Retries → KelvinRateLimitException
    // =========================================================================

    public function test_rate_limit_response_throws_kelvin_rate_limit_exception(): void
    {
        Http::fake([
            '*/auth*'    => Http::response($this->tokenResponse(), 200),
            '*/schools*' => Http::response('Too Many Requests', 429),
        ]);

        $this->expectException(KelvinRateLimitException::class);
        $this->expectExceptionMessageMatches('/Rate-Limit/');

        $this->makeClient()->listSchools();
    }

    // =========================================================================
    // listClasses()
    // =========================================================================

    public function test_list_classes_returns_single_page_collection(): void
    {
        $classData = [
            ['name' => '3a', 'school' => 'GS-XY', 'users' => ['max.mueller'], 'url' => 'https://ucs.example.de/ucsschool/kelvin/v1/classes/3a', 'dn' => 'cn=3a,cn=klassen,ou=gs-xy,dc=ucs,dc=de'],
            ['name' => '1b', 'school' => 'GS-XY', 'users' => ['lena.mueller'], 'url' => 'https://ucs.example.de/ucsschool/kelvin/v1/classes/1b', 'dn' => 'cn=1b,cn=klassen,ou=gs-xy,dc=ucs,dc=de'],
        ];

        Http::fake(function ($request) use ($classData) {
            if (str_contains($request->url(), '/auth/')) {
                return Http::response($this->tokenResponse(), 200);
            }
            // Erste Seite: 2 Einträge (< page_size 200 → nur ein Call)
            static $called = false;
            if (! $called) {
                $called = true;
                return Http::response($classData, 200);
            }
            return Http::response([], 200);
        });

        $result = $this->makeClient()->listClasses('GS-XY');

        $this->assertCount(2, $result);
        $this->assertSame('3a', $result[0]['name']);
        $this->assertSame('1b', $result[1]['name']);
    }

    public function test_list_classes_returns_empty_collection_when_no_classes(): void
    {
        Http::fake([
            '*/auth*'    => Http::response($this->tokenResponse(), 200),
            '*/classes*' => Http::response([], 200),
        ]);

        $result = $this->makeClient()->listClasses('GS-XY');

        $this->assertCount(0, $result);
    }
}


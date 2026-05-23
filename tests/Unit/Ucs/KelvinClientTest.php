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
    // Kriterium 1: Token-Caching – zweiter Aufruf ruft /token NICHT nochmal auf
    // =========================================================================

    public function test_token_is_cached_and_auth_called_only_once(): void
    {
        // Wildcard am Ende matcht auch URLs mit Query-Parametern
        Http::fake([
            '*/token'    => Http::response($this->tokenResponse(), 200),
            '*/schools*' => Http::response($this->schoolsResponse(), 200),
        ]);

        $client = $this->makeClient();

        // Zwei verschiedene API-Calls
        $client->ping();
        $client->listSchools();

        // /token darf nur einmal aufgerufen worden sein
        Http::assertSentCount(3); // 1× /token, 1× ping, 1× listSchools
    }

    // =========================================================================
    // Kriterium 2: Nach TTL-Ablauf wird ein neues /token ausgelöst
    // =========================================================================

    public function test_token_refreshed_after_ttl_expiry(): void
    {
        Http::fake([
            '*/token'    => Http::response($this->tokenResponse(), 200),
            '*/schools*' => Http::response($this->schoolsResponse(), 200),
        ]);

        $client = $this->makeClient();
        $client->ping(); // setzt Token in Cache

        // Manuell den Cache löschen (simuliert TTL-Ablauf)
        Cache::forget('ucs.kelvin.token');

        $client->listSchools(); // muss neues Token holen

        Http::assertSentCount(4); // 2× /token, 1× ping, 1× listSchools
    }

    // =========================================================================
    // Kriterium 3: 401 → einmaliger Force-Refresh; zweiter 401 → KelvinAuthException
    // =========================================================================

    public function test_401_triggers_single_force_refresh_then_succeeds(): void
    {
        $callCount = 0;

        Http::fake(function ($request) use (&$callCount) {
            if (str_ends_with(rtrim($request->url(), '/'), 'token')) {
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
        Http::assertSentCount(4); // 1× /token, 1× schools (401), 1× /token (re-auth), 1× schools (ok)
    }

    public function test_401_after_force_refresh_throws_kelvin_auth_exception(): void
    {
        Http::fake([
            '*/token'    => Http::response($this->tokenResponse(), 200),
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
            '*/token'    => Http::response($this->tokenResponse(), 200),
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
            '*/token'    => Http::response($this->tokenResponse('my-secret-bearer'), 200),
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
    // Kriterium 6: /users/ liefert alle Einträge in einem Request (kein Pagination)
    // =========================================================================

    public function test_list_parents_returns_all_users_in_single_request(): void
    {
        // Die Kelvin API liefert beim /users/-Endpunkt alle Einträge auf einmal.
        $page1 = json_decode(
            file_get_contents(base_path('tests/Fixtures/kelvin/legal_guardian_page1.json')),
            true
        );

        $usersCalled = 0;
        Http::fake(function ($request) use ($page1, &$usersCalled) {
            if (str_ends_with(rtrim($request->url(), '/'), 'token')) {
                return Http::response($this->tokenResponse(), 200);
            }
            $usersCalled++;
            // Liefert alle 2 Datensätze auf einmal (kein Paging)
            return Http::response($page1, 200);
        });

        $client  = $this->makeClient(['kelvin_page_size' => 2]);
        $parents = iterator_to_array($client->listParents('GS-XY'), false);

        $this->assertCount(2, $parents, '2 Elternteile aus Single-Request');
        $this->assertContainsOnlyInstancesOf(KelvinUserDto::class, $parents);
        $this->assertSame(1, $usersCalled, 'Nur 1 HTTP-Anfrage an /users/');
    }

    public function test_list_parents_large_response_yields_all_records(): void
    {
        // Simuliere 600 Records in einer einzigen Response
        $singleParent = json_decode(
            file_get_contents(base_path('tests/Fixtures/kelvin/legal_guardian_page1.json')),
            true
        )[0];

        $allUsers = array_fill(0, 600, $singleParent);

        Http::fake(function ($request) use ($allUsers) {
            if (str_ends_with(rtrim($request->url(), '/'), 'token')) {
                return Http::response($this->tokenResponse(), 200);
            }
            return Http::response($allUsers, 200);
        });

        $client = $this->makeClient(['kelvin_page_size' => 200]);
        $count  = 0;

        foreach ($client->listParents('GS-XY') as $dto) {
            $this->assertInstanceOf(KelvinUserDto::class, $dto);
            $count++;
        }

        $this->assertSame(600, $count, '600 Records aus Single-Response erwartet.');
    }

    // =========================================================================
    // Kriterium 7: ping() mit 404 wirft KelvinUnavailableException
    // =========================================================================

    public function test_ping_with_404_throws_kelvin_unavailable_exception(): void
    {
        Http::fake([
            '*/token'    => Http::response($this->tokenResponse(), 200),
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
            '*/token'    => Http::response($this->tokenResponse(), 200),
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
            '*/token'             => Http::response($this->tokenResponse(), 200),
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
            '*/token'             => Http::response($this->tokenResponse(), 200),
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
            if (str_ends_with(rtrim($request->url(), '/'), 'token')) {
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
            '*/token'    => Http::response($this->tokenResponse(), 200),
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
            if (str_ends_with(rtrim($request->url(), '/'), 'token')) {
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
            '*/token'    => Http::response($this->tokenResponse(), 200),
            '*/classes*' => Http::response([], 200),
        ]);

        $result = $this->makeClient()->listClasses('GS-XY');

        $this->assertCount(0, $result);
    }

    // =========================================================================
    // Kriterium: Proxy-Block → KelvinUnavailableException mit Hinweis
    // =========================================================================

    public function test_proxy_block_connection_exception_throws_kelvin_unavailable_with_hint(): void
    {
        // Simuliert einen Proxy-Block beim Token-Abruf (ConnectionException beim /token-Endpunkt)
        Http::fake(function ($request) {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        });

        try {
            $this->makeClient()->listSchools();
            $this->fail('KelvinUnavailableException erwartet');
        } catch (\App\Services\Ucs\Exceptions\KelvinUnavailableException $e) {
            $this->assertStringContainsStringIgnoringCase(
                'Proxy',
                $e->getMessage(),
                'Fehlermeldung muss "Proxy-Block?"-Hinweis enthalten.'
            );
        }
    }

    // =========================================================================
    // Kriterium: Schreibverbots-Architektur – kein PUT/PATCH/DELETE, POST nur /token
    // =========================================================================

    public function test_kelvin_client_contains_no_write_methods(): void
    {
        $source = file_get_contents(app_path('Services/Ucs/KelvinClient.php'));

        // ->post() ist nur für /token erlaubt; andere Schreibmethoden nie
        $this->assertStringNotContainsString('->put(',    $source, 'KelvinClient darf kein PUT verwenden.');
        $this->assertStringNotContainsString('->patch(',  $source, 'KelvinClient darf kein PATCH verwenden.');
        $this->assertStringNotContainsString('->delete(', $source, 'KelvinClient darf kein DELETE verwenden.');

        // ->post() darf nur bei Aufrufen vorkommen, die über tokenUrl() oder $url auf /token zeigen.
        // Prüfung: kein direktes ->post('irgendwas') mit hartem Pfad außer token-Varianten
        preg_match_all('/->post\(\s*[\'"]([^\'"]+)[\'"]/', $source, $matches);
        $allowedPostPaths = ['/auth/', 'auth/', '/ucsschool/kelvin/v1/auth/', '/token', 'token'];
        foreach ($matches[1] as $path) {
            $isAllowed = in_array(trim($path), $allowedPostPaths, true)
                || str_ends_with(trim($path), '/auth/')
                || str_ends_with(trim($path), '/token');
            $this->assertTrue(
                $isAllowed,
                "Unerlaubter POST-Endpunkt in KelvinClient: {$path}. Schreibzugriff auf UCS verboten (§1/§12)."
            );
        }
    }
}

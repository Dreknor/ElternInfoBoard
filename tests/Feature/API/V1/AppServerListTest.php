<?php

namespace Tests\Feature\API\V1;

use App\Model\User;
use App\Services\App\AppServerList;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Freigabeliste der ElternInfo-App: Abruf, Weitergabe über /v1/instance, Hinweis für Admins.
 */
class AppServerListTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://herausgeber.example/elterninfo/servers.json';

    private string $secretKey;

    /** Antwort des Herausgeber-Servers (wird je Test ausgetauscht) */
    private array $served = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Cache::flush();

        $pair = sodium_crypto_sign_keypair();
        $this->secretKey = sodium_crypto_sign_secretkey($pair);
        config([
            'app.url' => 'https://eltern.schule.example',
            'services.app.server_list_url' => self::URL,
            'services.app.server_list_public_key' => rtrim(strtr(base64_encode(sodium_crypto_sign_publickey($pair)), '+/', '-_'), '='),
        ]);
        Http::fake([self::URL => fn () => Http::response($this->served)]);
    }

    private function signed(array $servers, string $issuedAt = '2026-11-14', ?string $key = null): array
    {
        $payload = json_encode(['version' => 1, 'issued_at' => $issuedAt, 'servers' => $servers]);

        return [
            'payload' => base64_encode($payload),
            'signature' => base64_encode(sodium_crypto_sign_detached($payload, $key ?? $this->secretKey)),
        ];
    }

    private function serve(array $signed): void
    {
        $this->served = $signed;
    }

    #[Test]
    public function fetch_stores_list_and_instance_passes_it_on_unchanged(): void
    {
        $signed = $this->signed([['host' => 'eltern.schule.example', 'kind' => 'managed', 'valid_until' => '2027-08-31']]);
        $this->serve($signed);

        $this->artisan('app:server-list')->assertSuccessful();

        $this->getJson('/api/v1/instance')->assertOk()->assertJsonPath('data.app_server_list', $signed);
    }

    #[Test]
    public function forged_list_is_rejected(): void
    {
        $otherKey = sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair());
        $this->serve($this->signed([['host' => 'eltern.schule.example', 'kind' => 'managed', 'valid_until' => '2099-01-01']], key: $otherKey));

        $this->assertFalse(app(AppServerList::class)->fetch());
        $this->assertNull(app(AppServerList::class)->current());
    }

    #[Test]
    public function older_list_does_not_replace_newer_one(): void
    {
        $list = app(AppServerList::class);
        $this->serve($this->signed([], '2026-11-14'));
        $this->assertTrue($list->fetch());

        $this->serve($this->signed([['host' => 'eltern.schule.example', 'kind' => 'managed', 'valid_until' => '2099-01-01']], '2026-10-01'));
        $this->assertFalse($list->fetch());
        $this->assertSame('2026-11-14', $list->payload()['issued_at']);
    }

    #[Test]
    public function host_label_matches_app(): void
    {
        $this->assertSame('eltern.schule.de/eib', AppServerList::hostLabel('https://Eltern.Schule.de/eib/'));
        $this->assertSame('schule.de:8443', AppServerList::hostLabel('https://schule.de:8443'));
    }

    #[Test]
    public function status_follows_rules_of_the_app(): void
    {
        $list = app(AppServerList::class);
        $at = fn (string $date) => CarbonImmutable::parse($date.' 12:00');

        // nicht in der Liste: Sperre erst ab Stichtag
        $this->serve($this->signed([['host' => 'andere.schule.example', 'kind' => 'managed', 'valid_until' => '2030-01-01']], '2026-11-14'));
        $list->fetch();
        $status = $list->statusForThisServer($at('2026-11-15'));
        $this->assertSame('unlisted', $status['state']);
        $this->assertSame('2027-08-01', $status['deadline']->toDateString());
        $this->assertFalse($status['enforced']);

        // per Hash eingetragen (privat betrieben, nicht öffentlich gelistet)
        $this->travelTo($at('2027-09-01'));
        $this->serve($this->signed([['host_sha256' => hash('sha256', 'eltern.schule.example'), 'kind' => 'supported', 'valid_until' => '2028-07-31']], '2027-09-01'));
        $list->fetch();
        $this->assertSame('ok', $list->statusForThisServer($at('2027-09-05'))['state']);
        // alte Liste ist kein Problem, solange der Abruf klappt – sie wird nur bei Änderungen neu signiert
        $this->travelTo($at('2028-01-10'));
        $list->fetch();
        $this->assertSame('ok', $list->statusForThisServer($at('2028-01-12'))['state']);
        // Abruf klappt seit über 14 Tagen nicht: nur Hinweis, keine Sperre
        $this->assertSame('fetch_failing', $list->statusForThisServer($at('2028-02-01'))['state']);
        $this->travelBack();
        // kurz vor Ablauf
        $this->travelTo($at('2028-07-01'));
        $this->serve($this->signed([['host' => 'eltern.schule.example', 'kind' => 'supported', 'valid_until' => '2028-07-31']], '2028-07-01'));
        $list->fetch();
        $this->travelBack();
        $this->assertSame('expiring', $list->statusForThisServer($at('2028-07-05'))['state']);
        // abgelaufen: Kulanz bis valid_until + 30 Tage
        $expired = $list->statusForThisServer($at('2028-08-02'));
        $this->assertSame('expired', $expired['state']);
        $this->assertSame('2028-08-30', $expired['deadline']->toDateString());
        // unbefristet (bis zur Kündigung): nie abgelaufen, kein Hinweis „läuft aus“
        $this->travelTo($at('2030-01-01'));
        $this->serve($this->signed([['host' => 'eltern.schule.example', 'kind' => 'supported', 'valid_until' => null]], '2030-01-01'));
        $list->fetch();
        $this->assertSame('ok', $list->statusForThisServer($at('2030-01-02'))['state']);
        $this->assertSame('fetch_failing', $list->statusForThisServer($at('2030-02-01'))['state']);
        $this->travelBack();
    }

    #[Test]
    public function admin_sees_notice_only_when_app_is_used(): void
    {
        Permission::findOrCreate('edit settings', 'web');
        $admin = User::factory()->create(['password_changed_at' => now(), 'changePassword' => false]);
        $admin->givePermissionTo('edit settings');
        $parent = User::factory()->create(['password_changed_at' => now(), 'changePassword' => false]);

        $this->serve($this->signed([], now()->toDateString()));
        app(AppServerList::class)->fetch();

        $this->withoutVite()->actingAs($admin)->get('/dashboard')->assertViewHas('appServerNotice', null);

        $parent->createToken('iPhone – ElternInfo-App');
        Cache::flush();

        $this->withoutVite()->actingAs($admin)->get('/dashboard')
            ->assertSee('nicht für die ElternInfo-App freigeschaltet');
        $this->withoutVite()->actingAs($parent)->get('/dashboard')->assertViewHas('appServerNotice', null);
    }
}

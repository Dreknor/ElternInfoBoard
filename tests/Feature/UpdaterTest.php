<?php

namespace Tests\Feature;

use App\Model\User;
use App\Services\Updater\UpdateService;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UpdaterTest extends TestCase
{
    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();

        // Eigene Ablage, damit Tests den echten Updater-Status nicht berühren
        $this->storage = sys_get_temp_dir().DIRECTORY_SEPARATOR.'updater-test-'.uniqid();
        config(['updater.storage_path' => $this->storage, 'updater.enabled' => true]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);

        parent::tearDown();
    }

    private function admin(): User
    {
        Permission::firstOrCreate(['name' => 'manage updates', 'guard_name' => 'web']);
        $user = User::factory()->create(['changePassword' => false]);
        $user->givePermissionTo('manage updates');

        return $user;
    }

    private function state(): array
    {
        return json_decode(file_get_contents($this->storage.DIRECTORY_SEPARATOR.'state.json'), true);
    }

    public function test_user_without_permission_cannot_access_updater(): void
    {
        $user = User::factory()->create(['changePassword' => false]);

        $this->actingAs($user)->get('/settings/updater')->assertForbidden();
        $this->actingAs($user)->post('/settings/updater/start')->assertForbidden();
    }

    public function test_updater_can_be_disabled(): void
    {
        config(['updater.enabled' => false]);

        $this->actingAs($this->admin())->get('/settings/updater')->assertNotFound();
    }

    public function test_admin_sees_updater_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/settings/updater')
            ->assertOk()
            ->assertSee('Online-Update');
    }

    public function test_start_requests_update_and_sets_bypass_cookie(): void
    {
        $response = $this->actingAs($this->admin())
            ->post('/settings/updater/start', ['full' => '1']);

        $response->assertRedirect(route('updater.index'));
        $response->assertSessionHas('type', 'info');

        $state = $this->state();
        $this->assertSame('requested', $state['status']);
        $this->assertTrue($state['full']);

        // Cookie darf nicht verschlüsselt sein, sonst greift der Bypass nicht
        $cookie = $response->getCookie('laravel_maintenance', false);
        $this->assertNotNull($cookie);
        $this->assertTrue(MaintenanceModeBypassCookie::isValid($cookie->getValue(), $state['secret']));
    }

    public function test_update_cannot_be_requested_twice(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/settings/updater/start');

        $this->actingAs($admin)->post('/settings/updater/start')
            ->assertSessionHas('type', 'danger');
    }

    public function test_request_can_be_cancelled(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/settings/updater/start');

        $this->actingAs($admin)->post('/settings/updater/cancel')
            ->assertSessionHas('type', 'success');

        $this->assertSame('idle', $this->state()['status']);
        $this->assertFalse(app(UpdateService::class)->isRequested());
    }

    public function test_status_does_not_expose_secret(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/settings/updater/start');

        $this->actingAs($admin)->getJson('/settings/updater/status')
            ->assertOk()
            ->assertJsonPath('state.status', 'requested')
            ->assertJsonMissingPath('state.secret');
    }

    public function test_crashed_run_is_reported_as_failed(): void
    {
        File::ensureDirectoryExists($this->storage);
        file_put_contents($this->storage.DIRECTORY_SEPARATOR.'state.json', json_encode([
            'status' => 'running',
            'steps' => ['migrate' => ['label' => 'Datenbank migrieren', 'status' => 'running', 'message' => null]],
        ]));

        $state = app(UpdateService::class)->state();

        $this->assertSame('failed', $state['status']);
        $this->assertSame('failed', $state['steps']['migrate']['status']);
    }

    public function test_run_if_requested_does_nothing_without_request(): void
    {
        $this->artisan('updater:run --if-requested')->assertSuccessful();

        $this->assertFileDoesNotExist($this->storage.DIRECTORY_SEPARATOR.'state.json');
    }
}

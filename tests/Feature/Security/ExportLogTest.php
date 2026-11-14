<?php

namespace Tests\Feature\Security;

use App\Model\SecurityEvent;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ExportLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function userWith(string $permission): User
    {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user = User::factory()->create(['password_changed_at' => now(), 'changePassword' => false]);
        $user->givePermissionTo($permission);

        return $user;
    }

    /** @test */
    public function schickzeiten_export_is_logged_with_technical_context_only(): void
    {
        $user = $this->userWith('download schickzeiten');

        $this->actingAs($user)->get('/schickzeiten/download')->assertOk();

        $event = SecurityEvent::where('event', 'export.download')->firstOrFail();
        $this->assertSame($user->id, $event->user_id);
        $this->assertSame(['export' => 'schickzeiten', 'format' => 'xlsx'], $event->context);
    }

    /** @test */
    public function krankmeldungen_export_without_permission_is_not_logged(): void
    {
        $this->actingAs(User::factory()->create(['changePassword' => false, 'password_changed_at' => now()]))
            ->get('/krankmeldung/download');

        $this->assertSame(0, SecurityEvent::where('event', 'export.download')->count());
    }

    /** @test */
    public function krankmeldungen_export_is_logged_with_permission(): void
    {
        $user = $this->userWith('download krankmeldungen');
        $this->actingAs($user)->get('/krankmeldung/download')->assertOk();

        $event = SecurityEvent::where('event', 'export.download')->firstOrFail();
        $this->assertSame('krankmeldungen', $event->context['export']);
        $this->assertSame('pdf', $event->context['format']);
        $this->assertSame(0, $event->context['rows']);
    }

    /** @test */
    public function denied_schickzeiten_export_is_not_logged(): void
    {
        $user = User::factory()->create(['password_changed_at' => now(), 'changePassword' => false]);

        $this->actingAs($user)->get('/schickzeiten/download')->assertForbidden();

        $this->assertSame(0, SecurityEvent::where('event', 'export.download')->count());
    }
}

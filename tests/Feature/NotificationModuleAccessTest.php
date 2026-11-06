<?php

namespace Tests\Feature;

use App\Jobs\SendPushNotifications;
use App\Model\Group;
use App\Model\Notification;
use App\Model\User;
use App\Model\Vertretung;
use App\Services\Notifications\NotificationAccess;
use App\Services\Notifications\PushDispatcher;
use App\Model\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\ActivatesModules;
use Tests\TestCase;

/**
 * Modulbezogene Benachrichtigungen (z. B. Vertretungsplan) nur an Nutzer,
 * die das Modulrecht (noch) haben und solange das Modul aktiv ist.
 */
class NotificationModuleAccessTest extends TestCase
{
    use ActivatesModules, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('view vertretungsplan', 'web');
        Permission::findOrCreate('edit vertretungsplan', 'web');
    }

    private function vertretungsplanModule(): void
    {
        $module = $this->activateModule('Vertretungsplan', ['view vertretungsplan']);
        $module->options = array_merge($module->options, ['adm-nav' => ['adm-rights' => ['edit vertretungsplan']]]);
        $module->save();
    }

    public function test_vertretung_benachrichtigt_nur_nutzer_mit_modulrecht(): void
    {
        $this->vertretungsplanModule();

        $mitRecht = User::factory()->create();
        $mitRecht->givePermissionTo('view vertretungsplan');
        $ohneRecht = User::factory()->create();
        $verwaltung = User::factory()->create();
        $verwaltung->givePermissionTo('edit vertretungsplan');

        $group = Group::factory()->create(['name' => '3a']);
        $group->users()->attach([$mitRecht->id, $ohneRecht->id, $verwaltung->id]);

        Vertretung::create([
            'date' => now()->addDay()->toDateString(),
            'klasse' => $group->id,
            'stunde' => 2,
            'altFach' => 'MA',
            'neuFach' => 'DE',
        ]);

        $this->assertDatabaseHas('notifications', ['user_id' => $mitRecht->id, 'type' => 'vertretung']);
        $this->assertDatabaseHas('notifications', ['user_id' => $verwaltung->id, 'type' => 'vertretung']);
        $this->assertDatabaseMissing('notifications', ['user_id' => $ohneRecht->id, 'type' => 'vertretung']);
    }

    public function test_keine_vertretungsbenachrichtigung_bei_deaktiviertem_modul(): void
    {
        $module = $this->activateModule('Vertretungsplan', ['view vertretungsplan']);
        $module->options = array_merge($module->options, ['active' => '0']);
        $module->save();

        $user = User::factory()->create();
        $user->givePermissionTo('view vertretungsplan');

        Notification::create(['user_id' => $user->id, 'type' => 'Vertretung', 'title' => 'x', 'message' => 'y', 'url' => url('vertretungsplan')]);

        $this->assertDatabaseMissing('notifications', ['user_id' => $user->id]);
    }

    public function test_benachrichtigung_ohne_modulbezug_bleibt_unveraendert(): void
    {
        $user = User::factory()->create();

        Notification::create(['user_id' => $user->id, 'type' => 'info', 'title' => 'Hinweis', 'message' => 'Text']);

        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'title' => 'Hinweis']);
    }

    public function test_modul_wird_auch_ueber_die_url_erkannt(): void
    {
        $this->vertretungsplanModule();
        $user = User::factory()->create();

        $this->assertFalse(NotificationAccess::allows($user, 'Erinnerung', url('vertretungsplan')));
        $this->assertTrue(NotificationAccess::allows($user, 'Erinnerung', url('post/3')));

        $user->givePermissionTo('view vertretungsplan');
        $this->assertTrue(NotificationAccess::allows($user->fresh(), 'Erinnerung', url('vertretungsplan')));
    }

    public function test_push_nur_an_nutzer_mit_modulrecht(): void
    {
        Queue::fake();
        $this->vertretungsplanModule();

        $mitRecht = User::factory()->create();
        $mitRecht->givePermissionTo('view vertretungsplan');
        $ohneRecht = User::factory()->create();
        foreach ([$mitRecht, $ohneRecht] as $user) {
            UserDevice::create(['user_id' => $user->id, 'token' => 'token-'.$user->id, 'provider' => 'fcm', 'platform' => 'android']);
        }

        PushDispatcher::dispatch([$mitRecht->id, $ohneRecht->id], 'Vertretung', 'Text', url('vertretungsplan'), 'Vertretung');

        Queue::assertPushed(SendPushNotifications::class, fn ($job) => $job->userIds === [$mitRecht->id]);
    }
}

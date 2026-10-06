<?php

namespace Tests\Feature\API\V1;

use App\Http\Controllers\NachrichtenController;
use App\Jobs\SendPushNotifications;
use App\Model\Liste;
use App\Model\Notification;
use App\Model\NotificationPreference;
use App\Model\UserDevice;
use App\Model\Vertretung;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationPreferences;
use App\Services\Push\FcmSender;
use App\Services\Push\NativePushService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

class NotificationSettingsTest extends AppApiTestCase
{
    /** @test */
    public function settings_have_defaults_and_can_be_changed_per_category_and_channel(): void
    {
        $user = $this->parentIn($this->group());
        Permission::findOrCreate(NotificationCategory::DISABLE_NEWS_MAIL_PERMISSION, 'web');
        $user->givePermissionTo(NotificationCategory::DISABLE_NEWS_MAIL_PERMISSION);
        Sanctum::actingAs($user);

        $categories = collect($this->getJson('/api/v1/me/notification-settings')->assertOk()->json('data.categories'))->keyBy('key');
        $this->assertSame(['app' => true, 'web' => true, 'mail' => true], $categories['nachrichten']['channels']);
        $this->assertTrue($categories['erinnerungen']['channels']['mail']);
        // Kategorien ohne aktives Modul (hier: Termine, Messenger) werden nicht angeboten
        $this->assertFalse($categories->has('termine'));
        $this->assertFalse($categories->has('messenger'));
        $this->assertFalse(NotificationCategory::supports('termine', 'mail'));

        $this->putJson('/api/v1/me/notification-settings', [
            'categories' => ['nachrichten' => ['app' => false, 'mail' => false], 'termine' => ['mail' => true]],
            'digest' => 'weekly',
        ])->assertOk()->assertJsonPath('data.digest', 'weekly');

        $this->assertFalse(NotificationPreferences::allows($user, 'nachrichten', 'app'));
        $this->assertTrue(NotificationPreferences::allows($user, 'nachrichten', 'web'));
        $this->assertFalse(NotificationPreferences::allows($user, 'nachrichten', 'mail'));
        $this->assertSame('weekly', $user->fresh()->benachrichtigung);
        // Kanal, den es für die Kategorie nicht gibt, wird ignoriert
        $this->assertDatabaseMissing('notification_preferences', ['user_id' => $user->id, 'category' => 'termine']);
    }

    /** @test */
    public function news_mail_can_only_be_disabled_with_permission(): void
    {
        Permission::findOrCreate(NotificationCategory::DISABLE_NEWS_MAIL_PERMISSION, 'web');
        $user = $this->parentIn($this->group());
        Sanctum::actingAs($user);

        $nachrichten = collect($this->getJson('/api/v1/me/notification-settings')->assertOk()->json('data.categories'))->keyBy('key')['nachrichten'];
        $this->assertSame(['mail'], $nachrichten['locked']);
        $this->assertTrue($nachrichten['channels']['mail']);

        $this->putJson('/api/v1/me/notification-settings', [
            'categories' => ['nachrichten' => ['app' => false, 'mail' => false]],
        ])->assertOk();

        $this->assertFalse(NotificationPreferences::allows($user, 'nachrichten', 'app'));
        $this->assertTrue(NotificationPreferences::allows($user, 'nachrichten', 'mail'));
        $this->assertDatabaseMissing('notification_preferences', ['user_id' => $user->id, 'category' => 'nachrichten', 'mail' => false]);

        // Früher gespeichertes „aus“ gilt ohne Recht nicht
        NotificationPreference::where('user_id', $user->id)->update(['mail' => false]);
        $this->assertTrue(NotificationPreferences::allows($user, 'nachrichten', 'mail'));
        $this->assertSame([$user->id], NotificationPreferences::filter([$user->id], 'nachrichten', 'mail'));

        $user->givePermissionTo(NotificationCategory::DISABLE_NEWS_MAIL_PERMISSION);
        $user = $user->fresh();
        $this->assertFalse(NotificationPreferences::allows($user, 'nachrichten', 'mail'));
        $this->assertSame([], NotificationPreferences::filter([$user->id], 'nachrichten', 'mail'));
    }

    /** @test */
    public function categories_are_resolved_from_type_and_url(): void
    {
        $this->assertSame(NotificationCategory::NACHRICHTEN, NotificationCategory::resolve('Ex. Angebot'));
        $this->assertSame(NotificationCategory::ERINNERUNGEN, NotificationCategory::resolve('Lesebestätigung'));
        $this->assertSame(NotificationCategory::HORT, NotificationCategory::resolve('Anwesenheitsabfrage'));
        $this->assertSame(NotificationCategory::VERTRETUNGSPLAN, NotificationCategory::resolve('info', url('vertretungsplan')));
        $this->assertSame(NotificationCategory::TERMINE, NotificationCategory::resolve('info', url('listen/4')));
        $this->assertSame(NotificationCategory::SYSTEM, NotificationCategory::resolve('info'));
    }

    /** @test */
    public function app_push_is_only_sent_to_users_who_allow_the_channel(): void
    {
        $group = $this->group();
        $muted = $this->parentIn($group);
        $active = $this->parentIn($group);
        UserDevice::create(['user_id' => $muted->id, 'token' => 'tok-muted', 'provider' => UserDevice::PROVIDER_FCM]);
        UserDevice::create(['user_id' => $active->id, 'token' => 'tok-active', 'provider' => UserDevice::PROVIDER_FCM]);
        NotificationPreference::create(['user_id' => $muted->id, 'category' => 'nachrichten', 'app' => false]);

        $this->mock(FcmSender::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('send')->once()->withArgs(fn ($token) => $token === 'tok-active')->andReturn(true);
        });

        (new SendPushNotifications([$muted->id, $active->id], 'Neue Nachricht', 'Elternabend', url('post/1'), 'Nachrichten'))
            ->handle(app(NativePushService::class));

        // Systemhinweise sind nicht abschaltbar
        $this->assertSame([$muted->id], NotificationPreferences::filter([$muted->id], NotificationCategory::SYSTEM, 'app'));
    }

    /** @test */
    public function publishing_a_post_dispatches_push(): void
    {
        $group = $this->group();
        $parent = $this->parentIn($group);
        UserDevice::create(['user_id' => $parent->id, 'token' => 'tok', 'provider' => UserDevice::PROVIDER_FCM]);
        $post = $this->postIn($group);

        app(NachrichtenController::class)->push($post);

        $this->assertDatabaseHas('notifications', ['user_id' => $parent->id, 'url' => url('post/'.$post->id)]);
        Queue::assertPushed(SendPushNotifications::class, fn ($job) => $job->userIds === [$parent->id]
            && $job->url === url('post/'.$post->id));
    }

    /** @test */
    public function substitutions_notify_the_class_once_and_dispatch_push(): void
    {
        $group = $this->group('3b');
        $parent = $this->parentIn($group);
        UserDevice::create(['user_id' => $parent->id, 'token' => 'tok', 'provider' => UserDevice::PROVIDER_FCM]);

        Vertretung::create(['date' => today()->addDay()->toDateString(), 'klasse' => $group->id, 'stunde' => 2, 'altFach' => 'Ma']);
        // Neues System über die Kurzform der Klasse; ungelesene Benachrichtigung wird aktualisiert
        Vertretung::create(['date' => today()->addDay()->toDateString(), 'klasse_kurzform' => '3b', 'stunde' => 4, 'altFach' => 'De']);

        $notifications = Notification::where('user_id', $parent->id)->where('type', 'vertretung')->get();
        $this->assertCount(1, $notifications);
        $this->assertStringContainsString('4. Stunde', $notifications->first()->message);
        Queue::assertPushed(SendPushNotifications::class);
    }

    /** @test */
    public function activating_a_list_notifies_its_groups(): void
    {
        Permission::findOrCreate('edit terminliste', 'web');
        $group = $this->group();
        $parent = $this->parentIn($group);
        $owner = $this->parentIn($this->group('Verwaltung'));
        $owner->givePermissionTo('edit terminliste');
        $liste = Liste::create(['listenname' => 'Elterngespräche', 'type' => 'termin', 'besitzer' => $owner->id,
            'visible_for_all' => false, 'active' => false, 'ende' => now()->addWeek(), 'multiple' => false, 'duration' => 15]);
        DB::table('group_listen')->insert(['group_id' => $group->id, 'liste_id' => $liste->id]);

        $this->actingAs($owner)->post("/listen/{$liste->id}/activate")->assertRedirect();

        $this->assertTrue((bool) $liste->fresh()->active);
        $this->assertDatabaseHas('notifications', ['user_id' => $parent->id, 'type' => 'Listen', 'url' => url('listen/'.$liste->id)]);
    }

    /** @test */
    public function web_settings_form_saves_unchecked_channels_as_off(): void
    {
        $user = $this->parentIn($this->group());

        $this->actingAs($user)->put('/einstellungen', [
            'name' => $user->name,
            'email' => $user->email,
            'benachrichtigung' => 'daily',
            'sendCopy' => 1,
            'notifications_present' => 1,
            'notifications' => ['nachrichten' => ['app' => 1]],
        ])->assertRedirect();

        $this->assertTrue(NotificationPreferences::allows($user, 'nachrichten', 'app'));
        $this->assertFalse(NotificationPreferences::allows($user, 'nachrichten', 'web'));
        // Ohne Recht „disable news mail“ bleibt die E-Mail-Zusammenfassung aktiv
        $this->assertTrue(NotificationPreferences::allows($user, 'nachrichten', 'mail'));
        $this->assertFalse(NotificationPreferences::allows($user, 'erinnerungen', 'app'));
        $this->assertFalse(NotificationPreferences::allows($user, 'erinnerungen', 'mail'));
    }

    /** @test */
    public function web_settings_page_shows_channel_matrix(): void
    {
        $user = $this->parentIn($this->group());
        NotificationPreference::create(['user_id' => $user->id, 'category' => 'nachrichten', 'web' => false]);

        $html = $this->actingAs($user)->get('/einstellungen')->assertOk()->getContent();

        $this->assertStringContainsString('name="notifications_present"', $html);
        $this->assertStringContainsString('name="notifications[erinnerungen][mail]"', $html);
        $this->assertMatchesRegularExpression('/name="notifications\[nachrichten\]\[app\]"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="notifications\[nachrichten\]\[web\]"[^>]*checked/', $html);
    }

    /** @test */
    public function legacy_app_switches_are_mirrored(): void
    {
        $user = $this->parentIn($this->group());
        Sanctum::actingAs($user);

        $this->patchJson('/api/user/settings', ['path' => 'push.messenger', 'value' => false])->assertOk();

        $this->assertFalse(NotificationPreferences::allows($user, 'messenger', 'app'));
        $this->assertTrue(NotificationPreferences::allows($user, 'messenger', 'web'));
    }
}

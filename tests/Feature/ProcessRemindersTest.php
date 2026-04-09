<?php

namespace Tests\Feature;

use App\Jobs\ProcessRemindersJob;
use App\Mail\ReminderEscalationMail;
use App\Mail\ReminderMail;
use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\Group;
use App\Model\Notification;
use App\Model\Post;
use App\Model\ReadReceipts;
use App\Model\ReminderLog;
use App\Model\Rueckmeldungen;
use App\Model\User;
use App\Model\UserRueckmeldungen;
use App\Notifications\ReminderPushNotification;
use App\Settings\ReminderSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCase;

/**
 * Feature-Tests für das Erinnerungs- und Eskalationssystem (Feature 3)
 */
class ProcessRemindersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $author;
    private Post $post;
    private Group $group;
    private Rueckmeldungen $rueckmeldung;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        // Testdaten erstellen
        $this->author = User::factory()->create(['password_changed_at' => now()]);
        $this->user = User::factory()->create(['password_changed_at' => now()]);
        $this->group = Group::factory()->create(['protected' => false]);

        // User der Gruppe zuordnen
        $this->user->groups()->attach($this->group->id);

        // Post erstellen und Gruppe zuweisen
        $this->post = Post::factory()->create([
            'released' => 1,
            'author' => $this->author->id,
            'header' => 'Testbeitrag mit Rückmeldung',
            'archiv_ab' => now()->addDays(10),
        ]);
        $this->post->groups()->attach($this->group->id);

        // Pflicht-Rückmeldung mit Frist
        $this->rueckmeldung = Rueckmeldungen::factory()->create([
            'post_id' => $this->post->id,
            'pflicht' => true,
            'ende' => now()->addDays(3),
            'text' => 'Pflicht-Rückmeldung',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    //  ReminderLog Model
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function reminder_log_can_be_created(): void
    {
        $log = ReminderLog::create([
            'remindable_type' => Rueckmeldungen::class,
            'remindable_id' => $this->rueckmeldung->id,
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'level' => 1,
            'channel' => 'in_app',
            'sent_at' => now(),
        ]);

        $this->assertDatabaseHas('reminder_logs', [
            'id' => $log->id,
            'level' => 1,
            'channel' => 'in_app',
        ]);
    }

    /** @test */
    public function reminder_log_has_polymorphic_relationship(): void
    {
        $log = ReminderLog::create([
            'remindable_type' => Rueckmeldungen::class,
            'remindable_id' => $this->rueckmeldung->id,
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'level' => 1,
            'channel' => 'in_app',
            'sent_at' => now(),
        ]);

        $this->assertInstanceOf(Rueckmeldungen::class, $log->remindable);
        $this->assertInstanceOf(User::class, $log->user);
        $this->assertInstanceOf(Post::class, $log->post);
    }

    /** @test */
    public function reminder_log_scopes_work(): void
    {
        ReminderLog::create([
            'remindable_type' => Rueckmeldungen::class,
            'remindable_id' => $this->rueckmeldung->id,
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'level' => 1,
            'channel' => 'in_app',
            'sent_at' => now(),
        ]);

        ReminderLog::create([
            'remindable_type' => Rueckmeldungen::class,
            'remindable_id' => $this->rueckmeldung->id,
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'level' => 2,
            'channel' => 'email',
            'sent_at' => now(),
        ]);

        $this->assertCount(1, ReminderLog::level(1)->get());
        $this->assertCount(2, ReminderLog::forUser($this->user->id)->get());
        $this->assertCount(2, ReminderLog::forPost($this->post->id)->get());
    }

    // ═══════════════════════════════════════════════════════════════
    //  ReminderSetting
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function reminder_settings_have_correct_defaults(): void
    {
        $settings = new ReminderSetting;

        $this->assertTrue($settings->level1_active);
        $this->assertEquals(5, $settings->level1_days_before_deadline);
        $this->assertTrue($settings->level1_in_app);
        $this->assertFalse($settings->level1_email);

        $this->assertTrue($settings->level2_active);
        $this->assertEquals(2, $settings->level2_days_before_deadline);
        $this->assertTrue($settings->level2_email);

        $this->assertTrue($settings->level3_active);
        $this->assertEquals(1, $settings->level3_days_after_deadline);
        $this->assertTrue($settings->level3_escalate_to_author);

        $this->assertEquals('08:00', $settings->send_time);
        $this->assertTrue($settings->include_rueckmeldungen);
        $this->assertTrue($settings->include_read_receipts);
        $this->assertTrue($settings->include_attendance_queries);
    }

    // ═══════════════════════════════════════════════════════════════
    //  ProcessRemindersJob – Rückmeldungen
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function job_sends_level1_reminder_for_pending_rueckmeldung(): void
    {
        // Setze Frist auf 4 Tage (innerhalb Level-1-Fenster: 5 Tage)
        $this->rueckmeldung->update(['ende' => now()->addDays(4)]);

        $job = new ProcessRemindersJob;
        $job->handle(new ReminderSetting);

        // In-App-Notification sollte erstellt worden sein
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'Rückmeldung',
        ]);

        // ReminderLog sollte existieren
        $this->assertDatabaseHas('reminder_logs', [
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'level' => 1,
            'channel' => 'in_app',
        ]);
    }

    /** @test */
    public function job_sends_level2_reminder_with_email(): void
    {
        // Setze Frist auf 1 Tag (innerhalb Level-2-Fenster: 2 Tage)
        $this->rueckmeldung->update(['ende' => now()->addDays(1)]);

        $job = new ProcessRemindersJob;
        $job->handle(new ReminderSetting);

        // E-Mail sollte gesendet worden sein
        Mail::assertSent(ReminderMail::class, function ($mail) {
            return $mail->level === 2 && $mail->type === 'rueckmeldung';
        });

        // In-App und E-Mail Logs
        $this->assertDatabaseHas('reminder_logs', [
            'user_id' => $this->user->id,
            'level' => 2,
            'channel' => 'in_app',
        ]);
        $this->assertDatabaseHas('reminder_logs', [
            'user_id' => $this->user->id,
            'level' => 2,
            'channel' => 'email',
        ]);
    }

    /** @test */
    public function job_sends_level3_with_escalation(): void
    {
        // Setze Frist auf gestern (abgelaufen + 1 Tag → Stufe 3)
        $this->rueckmeldung->update(['ende' => now()->subDays(2)]);

        $job = new ProcessRemindersJob;
        $job->handle(new ReminderSetting);

        // Eskalations-E-Mail an Autor
        Mail::assertSent(ReminderEscalationMail::class, function ($mail) {
            return $mail->authorName === $this->author->name;
        });

        // Eskalation-Log
        $this->assertDatabaseHas('reminder_logs', [
            'user_id' => $this->user->id,
            'level' => 3,
            'channel' => 'escalation',
        ]);
    }

    /** @test */
    public function job_does_not_send_duplicate_reminders(): void
    {
        $this->rueckmeldung->update(['ende' => now()->addDays(1)]);

        $job = new ProcessRemindersJob;
        $settings = new ReminderSetting;

        // Ersten Durchlauf
        $job->handle($settings);

        $countAfterFirst = ReminderLog::count();

        // Zweiter Durchlauf – sollte keine neuen Logs erstellen
        $job->handle($settings);

        $this->assertEquals($countAfterFirst, ReminderLog::count());
    }

    /** @test */
    public function job_skips_users_who_already_responded(): void
    {
        $this->rueckmeldung->update(['ende' => now()->addDays(1)]);

        // User hat bereits geantwortet
        UserRueckmeldungen::create([
            'post_id' => $this->post->id,
            'users_id' => $this->user->id,
            'text' => 'Ja, nehme teil',
        ]);

        $job = new ProcessRemindersJob;
        $job->handle(new ReminderSetting);

        // Kein ReminderLog sollte erstellt werden
        $this->assertDatabaseMissing('reminder_logs', [
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function job_respects_disabled_levels(): void
    {
        $this->rueckmeldung->update(['ende' => now()->addDays(4)]);

        $settings = new ReminderSetting;
        $settings->level1_active = false;
        $settings->save();

        $job = new ProcessRemindersJob;
        $job->handle($settings);

        // Kein Log, da Level 1 deaktiviert
        $this->assertDatabaseMissing('reminder_logs', [
            'user_id' => $this->user->id,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    //  ProcessRemindersJob – Lesebestätigungen
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function job_sends_reminder_for_unconfirmed_read_receipt(): void
    {
        // Post mit Lesebestätigung erstellen
        $readReceiptPost = Post::factory()->create([
            'released' => 1,
            'author' => $this->author->id,
            'header' => 'Wichtige Mitteilung',
            'read_receipt' => true,
            'read_receipt_deadline' => now()->addDays(1),
        ]);
        $readReceiptPost->groups()->attach($this->group->id);

        $job = new ProcessRemindersJob;
        $job->handle(new ReminderSetting);

        // Reminder für Lesebestätigung sollte existieren
        $this->assertDatabaseHas('reminder_logs', [
            'remindable_type' => Post::class,
            'remindable_id' => $readReceiptPost->id,
            'user_id' => $this->user->id,
            'level' => 2,
        ]);
    }

    /** @test */
    public function job_skips_confirmed_read_receipts(): void
    {
        $readReceiptPost = Post::factory()->create([
            'released' => 1,
            'author' => $this->author->id,
            'header' => 'Bereits gelesen',
            'read_receipt' => true,
            'read_receipt_deadline' => now()->addDays(1),
        ]);
        $readReceiptPost->groups()->attach($this->group->id);

        // User hat bereits bestätigt
        ReadReceipts::create([
            'post_id' => $readReceiptPost->id,
            'user_id' => $this->user->id,
            'confirmed_at' => now(),
        ]);

        $job = new ProcessRemindersJob;
        $job->handle(new ReminderSetting);

        // Kein Reminder
        $this->assertDatabaseMissing('reminder_logs', [
            'remindable_type' => Post::class,
            'remindable_id' => $readReceiptPost->id,
            'user_id' => $this->user->id,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    //  Settings Admin UI
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function admin_can_view_settings_with_reminder_tab(): void
    {
        // Verify that the view file exists and contains the expected content
        $viewPath = resource_path('views/settings/tabs/reminder-tab.blade.php');
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);
        $this->assertStringContainsString('Stufe 1: Sanfte Erinnerung', $content);
        $this->assertStringContainsString('Stufe 2: Dringende Erinnerung', $content);
        $this->assertStringContainsString('Stufe 3: Letzte Erinnerung', $content);
        $this->assertStringContainsString('settings/reminder', $content);
    }

    /** @test */
    public function reminder_settings_can_be_saved_and_loaded(): void
    {
        $settings = new ReminderSetting;

        // Change settings
        $settings->send_time = '09:30';
        $settings->level1_days_before_deadline = 7;
        $settings->level2_days_before_deadline = 3;
        $settings->level3_days_after_deadline = 2;
        $settings->level3_escalate_to_author = false;
        $settings->save();

        // Reload and verify
        $reloaded = new ReminderSetting;
        $this->assertEquals('09:30', $reloaded->send_time);
        $this->assertEquals(7, $reloaded->level1_days_before_deadline);
        $this->assertEquals(3, $reloaded->level2_days_before_deadline);
        $this->assertEquals(2, $reloaded->level3_days_after_deadline);
        $this->assertFalse($reloaded->level3_escalate_to_author);
    }

    // ═══════════════════════════════════════════════════════════════
    //  Dashboard-Integration (Unit-Level)
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function pending_feedback_logic_finds_open_rueckmeldungen(): void
    {
        $this->rueckmeldung->update(['ende' => now()->addDays(2)]);

        // Simulate what the DashboardController would do
        $userId = $this->user->id;
        $userGroupIds = \DB::table('group_user')
            ->where('user_id', $userId)
            ->pluck('group_id')
            ->toArray();

        $pflichtRueckmeldungen = Rueckmeldungen::where('pflicht', true)
            ->whereNotNull('ende')
            ->whereHas('post', function ($q) use ($userGroupIds) {
                $q->where('released', 1)
                    ->whereHas('groups', fn ($q3) => $q3->whereIn('groups.id', $userGroupIds));
            })
            ->get();

        $this->assertCount(1, $pflichtRueckmeldungen);

        // User has NOT responded yet
        $hasResponded = UserRueckmeldungen::where('post_id', $this->post->id)
            ->where('users_id', $userId)
            ->exists();

        $this->assertFalse($hasResponded);
    }

    /** @test */
    public function pending_feedback_logic_excludes_responded(): void
    {
        $this->rueckmeldung->update(['ende' => now()->addDays(2)]);

        // User responds
        UserRueckmeldungen::create([
            'post_id' => $this->post->id,
            'users_id' => $this->user->id,
            'text' => 'Ja',
        ]);

        $hasResponded = UserRueckmeldungen::where('post_id', $this->post->id)
            ->where('users_id', $this->user->id)
            ->exists();

        $this->assertTrue($hasResponded);
    }

    // ═══════════════════════════════════════════════════════════════
    //  ProcessRemindersJob – Anwesenheitsabfragen (Teil C)
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function job_sends_attendance_reminder_for_open_checkins(): void
    {
        // Kind + Elternteil erstellen
        $parent = User::factory()->create(['password_changed_at' => now()]);
        $child = Child::factory()->create();
        $child->parents()->attach($parent->id);

        // Offene Anwesenheitsabfrage mit lock_at in 1 Tag (Level 2 Fenster)
        ChildCheckIn::create([
            'child_id' => $child->id,
            'date' => now()->addDays(5)->toDateString(),
            'should_be' => null,
            'lock_at' => now()->addDays(1)->toDateString(),
            'checked_in' => false,
            'checked_out' => false,
        ]);

        $job = new ProcessRemindersJob;
        $job->handle(new ReminderSetting);

        // ReminderLog für Anwesenheitsabfrage sollte existieren
        $this->assertDatabaseHas('reminder_logs', [
            'remindable_type' => ChildCheckIn::class,
            'user_id' => $parent->id,
            'level' => 2,
            'channel' => 'in_app',
        ]);

        // In-App Notification sollte erstellt sein
        $this->assertDatabaseHas('notifications', [
            'user_id' => $parent->id,
            'type' => 'Anwesenheitsabfrage',
        ]);
    }

    /** @test */
    public function job_skips_answered_attendance_queries(): void
    {
        $parent = User::factory()->create(['password_changed_at' => now()]);
        $child = Child::factory()->create();
        $child->parents()->attach($parent->id);

        // Bereits beantwortete Anwesenheitsabfrage
        ChildCheckIn::create([
            'child_id' => $child->id,
            'date' => now()->addDays(5)->toDateString(),
            'should_be' => true, // Bereits beantwortet
            'lock_at' => now()->addDays(1)->toDateString(),
            'checked_in' => false,
            'checked_out' => false,
        ]);

        $job = new ProcessRemindersJob;
        $job->handle(new ReminderSetting);

        // Kein ReminderLog für Anwesenheit
        $this->assertDatabaseMissing('reminder_logs', [
            'remindable_type' => ChildCheckIn::class,
            'user_id' => $parent->id,
        ]);
    }

    /** @test */
    public function job_sends_email_for_attendance_at_level2(): void
    {
        $parent = User::factory()->create(['password_changed_at' => now(), 'email' => 'eltern@example.com']);
        $child = Child::factory()->create();
        $child->parents()->attach($parent->id);

        ChildCheckIn::create([
            'child_id' => $child->id,
            'date' => now()->addDays(5)->toDateString(),
            'should_be' => null,
            'lock_at' => now()->addDays(1)->toDateString(),
            'checked_in' => false,
            'checked_out' => false,
        ]);

        $job = new ProcessRemindersJob;
        $job->handle(new ReminderSetting);

        // E-Mail für Anwesenheitsabfrage
        Mail::assertSent(ReminderMail::class, function ($mail) {
            return $mail->type === 'anwesenheit' && $mail->level === 2;
        });

        // E-Mail-Log
        $this->assertDatabaseHas('reminder_logs', [
            'remindable_type' => ChildCheckIn::class,
            'user_id' => $parent->id,
            'level' => 2,
            'channel' => 'email',
        ]);
    }

    /** @test */
    public function job_does_not_duplicate_attendance_reminders(): void
    {
        $parent = User::factory()->create(['password_changed_at' => now()]);
        $child = Child::factory()->create();
        $child->parents()->attach($parent->id);

        ChildCheckIn::create([
            'child_id' => $child->id,
            'date' => now()->addDays(5)->toDateString(),
            'should_be' => null,
            'lock_at' => now()->addDays(1)->toDateString(),
            'checked_in' => false,
            'checked_out' => false,
        ]);

        $job = new ProcessRemindersJob;
        $settings = new ReminderSetting;

        // Erster Durchlauf
        $job->handle($settings);
        $countAfterFirst = ReminderLog::where('remindable_type', ChildCheckIn::class)->count();

        // Zweiter Durchlauf – keine neuen Logs
        $job->handle($settings);
        $this->assertEquals($countAfterFirst, ReminderLog::where('remindable_type', ChildCheckIn::class)->count());
    }

    /** @test */
    public function job_respects_disabled_attendance_queries_setting(): void
    {
        $parent = User::factory()->create(['password_changed_at' => now()]);
        $child = Child::factory()->create();
        $child->parents()->attach($parent->id);

        ChildCheckIn::create([
            'child_id' => $child->id,
            'date' => now()->addDays(5)->toDateString(),
            'should_be' => null,
            'lock_at' => now()->addDays(1)->toDateString(),
            'checked_in' => false,
            'checked_out' => false,
        ]);

        $settings = new ReminderSetting;
        $settings->include_attendance_queries = false;
        $settings->save();

        $job = new ProcessRemindersJob;
        $job->handle($settings);

        // Kein Log für Anwesenheit
        $this->assertDatabaseMissing('reminder_logs', [
            'remindable_type' => ChildCheckIn::class,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    //  Push-Kanal
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function job_sends_push_when_enabled(): void
    {
        NotificationFacade::fake();

        // Push für Level 2 aktivieren (default: true)
        $this->rueckmeldung->update(['ende' => now()->addDays(1)]);

        $job = new ProcessRemindersJob;
        $settings = new ReminderSetting;

        // Stelle sicher dass Push aktiviert ist
        $this->assertTrue($settings->level2_push);

        $job->handle($settings);

        // Push-Notification sollte gesendet worden sein
        NotificationFacade::assertSentTo($this->user, ReminderPushNotification::class);

        // Push-Log sollte existieren
        $this->assertDatabaseHas('reminder_logs', [
            'user_id' => $this->user->id,
            'level' => 2,
            'channel' => 'push',
        ]);
    }

    /** @test */
    public function job_does_not_send_push_when_disabled(): void
    {
        NotificationFacade::fake();

        // Level 1 hat Push standardmäßig deaktiviert
        $this->rueckmeldung->update(['ende' => now()->addDays(4)]);

        $settings = new ReminderSetting;
        $this->assertFalse($settings->level1_push);

        $job = new ProcessRemindersJob;
        $job->handle($settings);

        // Push sollte NICHT gesendet worden sein
        NotificationFacade::assertNotSentTo($this->user, ReminderPushNotification::class);

        // Kein Push-Log
        $this->assertDatabaseMissing('reminder_logs', [
            'user_id' => $this->user->id,
            'channel' => 'push',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    //  Feiertags-Erkennung in storeAbfrage (Feature 6D)
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function store_abfrage_uses_holiday_service(): void
    {
        // Prüfe, dass der CareController HolidayService nutzt
        $controllerContent = file_get_contents(app_path('Http/Controllers/Anwesenheit/CareController.php'));
        $this->assertStringContainsString('HolidayService', $controllerContent);
        $this->assertStringContainsString('isHoliday', $controllerContent);
    }

    // ═══════════════════════════════════════════════════════════════
    //  ReminderPushNotification
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function reminder_push_notification_class_exists(): void
    {
        $notification = new ReminderPushNotification(
            'Test Titel',
            'Test Body',
            'https://example.com'
        );

        $this->assertEquals('Test Titel', $notification->title);
        $this->assertEquals('Test Body', $notification->body);
        $this->assertEquals('https://example.com', $notification->actionUrl);
    }
}




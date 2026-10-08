<?php

namespace Tests\Feature;

use App\Exports\ReinigungExport;
use App\Mail\ReinigungChangeMail;
use App\Model\Group;
use App\Model\Module;
use App\Model\Notification;
use App\Model\Reinigung;
use App\Model\ReinigungsTask;
use App\Model\User;
use App\Services\App\TerminQuery;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationPreferences;
use App\Settings\ReinigungSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\ActivatesModules;
use Tests\TestCase;

/**
 * Benachrichtigung betroffener Familien bei Änderungen am Reinigungsplan
 * (Glocke/Push und E-Mail), Abhakliste im Export sowie Anzeige der eigenen
 * Einsätze bei den Terminen und im Dashboard.
 */
class ReinigungNotificationTest extends TestCase
{
    use ActivatesModules, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activateModule('Reinigung');

        (new ReinigungSetting)->fill([
            'separate_bereiche' => true,
            'combined_exclude_bereiche' => [],
            'skip_holidays' => false,
            'reminder_enabled' => false,
            'reminder_days_before' => 3,
            'reminder_email' => true,
            'reminder_push' => true,
            'reminder_time' => '08:00',
        ])->save();
    }

    private function editReinigungUser(): User
    {
        $user = User::factory()->create(['changePassword' => false]);
        Permission::findOrCreate('edit reinigung');
        $user->givePermissionTo('edit reinigung');

        return $user;
    }

    /** @return array{0: User, 1: User} */
    private function family(): array
    {
        $parent = User::factory()->create();
        $partner = User::factory()->create(['sorg2' => $parent->id]);
        $parent->update(['sorg2' => $partner->id]);

        return [$parent, $partner];
    }

    /** @test */
    public function store_notifies_the_whole_family(): void
    {
        $admin = $this->editReinigungUser();
        [$parent, $partner] = $this->family();
        $task = ReinigungsTask::factory()->create(['task' => 'Küche']);

        $this->actingAs($admin)->post('reinigung/Kindergarten', [
            'users_id' => $parent->id,
            'aufgabe' => $task->id,
            'datum' => now()->addWeek()->startOfWeek()->format('Y-m-d'),
            'bemerkung' => 'Fenster',
        ])->assertRedirect(url('reinigung'));

        foreach ([$parent, $partner] as $member) {
            $notification = Notification::where('user_id', $member->id)->where('type', 'Reinigung')->first();
            $this->assertNotNull($notification);
            $this->assertSame('Reinigungsplan: neuer Einsatz', $notification->title);
            $this->assertStringContainsString('Küche', $notification->message);
            $this->assertStringContainsString('Fenster', $notification->message);
        }
        $this->assertSame(0, Notification::where('user_id', $admin->id)->count());
    }

    /** @test */
    public function destroy_notifies_the_family_about_the_removal(): void
    {
        $admin = $this->editReinigungUser();
        [$parent, $partner] = $this->family();
        $reinigung = Reinigung::factory()->create([
            'users_id' => $parent->id,
            'bereich' => 'Kindergarten',
            'datum' => now()->addWeek()->format('Y-m-d'),
        ]);

        $this->actingAs($admin)->delete('reinigung/Kindergarten/'.$reinigung->id.'/trash');

        $this->assertDatabaseMissing('reinigung', ['id' => $reinigung->id]);
        $this->assertSame(1, Notification::where('user_id', $parent->id)->where('title', 'Reinigungsplan: Einsatz entfernt')->count());
        $this->assertSame(1, Notification::where('user_id', $partner->id)->where('title', 'Reinigungsplan: Einsatz entfernt')->count());
    }

    /** @test */
    public function past_entries_do_not_trigger_notifications(): void
    {
        $admin = $this->editReinigungUser();
        [$parent] = $this->family();
        $reinigung = Reinigung::factory()->create([
            'users_id' => $parent->id,
            'bereich' => 'Kindergarten',
            'datum' => now()->subWeeks(2)->format('Y-m-d'),
        ]);

        $this->actingAs($admin)->delete('reinigung/Kindergarten/'.$reinigung->id.'/trash');

        $this->assertSame(0, Notification::where('user_id', $parent->id)->count());
    }

    /** @test */
    public function autocreate_sends_one_summary_per_family(): void
    {
        $admin = $this->editReinigungUser();
        $group = Group::factory()->create(['bereich' => 'Kindergarten']);
        $family = User::factory()->create();
        $family->groups()->attach($group);
        $task = ReinigungsTask::factory()->create();

        $this->actingAs($admin)->post('reinigung/Kindergarten/auto', [
            'aufgaben' => [$task->id],
            'exclude' => [],
            'start' => now()->addWeek()->startOfWeek()->format('Y-m-d'),
            'end' => now()->addWeeks(3)->endOfWeek()->format('Y-m-d'),
        ])->assertSessionHas('type', 'success');

        $this->assertGreaterThan(1, Reinigung::where('users_id', $family->id)->count());
        $notifications = Notification::where('user_id', $family->id)->get();
        $this->assertCount(1, $notifications);
        $this->assertSame('Reinigungsplan: neue Einsätze', $notifications->first()->title);
    }

    /** @test */
    public function export_contains_remarks_as_checklist(): void
    {
        [$parent] = $this->family();
        Reinigung::factory()->create([
            'users_id' => $parent->id,
            'bereich' => 'Kindergarten',
            'datum' => now()->addWeek()->format('Y-m-d'),
            'aufgabe' => 'Küche',
            'bemerkung' => "Fenster\nBoden; Müll",
        ]);

        $export = new ReinigungExport('Kindergarten');
        $row = $export->map($export->collection()->first());

        $this->assertSame(ReinigungExport::CHECKBOX, $row[0]);
        $this->assertSame('Küche', $row[3]);
        $this->assertSame("☐ Fenster\n☐ Boden\n☐ Müll", $row[4]);
        $this->assertContains('Bemerkungen', $export->headings());
    }

    /** @test */
    public function export_download_renders_a_styled_spreadsheet(): void
    {
        $admin = $this->editReinigungUser();
        [$parent] = $this->family();
        Reinigung::factory()->create([
            'users_id' => $parent->id,
            'bereich' => 'Kindergarten',
            'datum' => now()->addWeek()->format('Y-m-d'),
            'bemerkung' => 'Fenster; Boden',
        ]);

        $response = $this->actingAs($admin)->get('reinigung/Kindergarten/export');

        $response->assertOk();
        $file = $response->baseResponse->getFile()->getPathname();
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file)->getActiveSheet();
        $this->assertSame('Erledigt', $sheet->getCell('A1')->getValue());
        $this->assertSame("☐ Fenster\n☐ Boden", $sheet->getCell('E2')->getValue());
    }

    /** @test */
    public function store_sends_mails_to_the_family_respecting_preferences(): void
    {
        Mail::fake();
        $admin = $this->editReinigungUser();
        [$parent, $partner] = $this->family();
        NotificationPreferences::set($partner->id, NotificationCategory::ORGANISATION, 'mail', false);
        $task = ReinigungsTask::factory()->create(['task' => 'Küche']);

        $this->actingAs($admin)->post('reinigung/Kindergarten', [
            'users_id' => $parent->id,
            'aufgabe' => $task->id,
            'datum' => now()->addWeek()->startOfWeek()->format('Y-m-d'),
            'bemerkung' => 'Fenster; Boden',
        ]);

        Mail::assertQueued(ReinigungChangeMail::class, function (ReinigungChangeMail $mail) use ($parent) {
            return $mail->hasTo($parent->email)
                && $mail->betreff === 'Reinigungsplan: neuer Einsatz'
                && $mail->einsaetze[0]['bemerkungen'] === ['Fenster', 'Boden'];
        });
        Mail::assertNotQueued(ReinigungChangeMail::class, fn (ReinigungChangeMail $mail) => $mail->hasTo($partner->email));
        Mail::assertNotQueued(ReinigungChangeMail::class, fn (ReinigungChangeMail $mail) => $mail->hasTo($admin->email));
    }

    /** @test */
    public function change_mail_renders_the_assignments(): void
    {
        $html = (new ReinigungChangeMail('Familie Muster', 'Reinigungsplan: neuer Einsatz', 'Sie wurden eingeteilt:', [
            ['woche' => '05.10. - 11.10.2026', 'aufgabe' => 'Küche', 'bemerkungen' => ['Fenster']],
        ]))->render();

        $this->assertStringContainsString('05.10. - 11.10.2026', $html);
        $this->assertStringContainsString('Küche', $html);
        $this->assertStringContainsString('Fenster', $html);
    }

    /** @test */
    public function termine_index_lists_own_cleaning_duties(): void
    {
        [$parent, $partner] = $this->family();
        $partner->update(['changePassword' => false]);
        Reinigung::factory()->create([
            'users_id' => $parent->id,
            'datum' => now()->addWeek()->format('Y-m-d'),
            'aufgabe' => 'Küche',
        ]);

        $response = $this->actingAs($partner)->get('termine');

        $response->assertOk();
        $response->assertViewHas('termine', fn ($termine) => $termine->contains(
            fn ($t) => $t->terminname === 'Reinigungsdienst: Küche' && $t->sourceUrl === url('reinigung')
        ));
        $response->assertSee('Reinigungsdienst: Küche');
    }

    /** @test */
    public function cleaning_duties_are_hidden_when_module_is_inactive(): void
    {
        $this->deactivateReinigung();
        [$parent] = $this->family();
        $parent->update(['changePassword' => false]);
        Reinigung::factory()->create([
            'users_id' => $parent->id,
            'datum' => now()->addWeek()->format('Y-m-d'),
            'aufgabe' => 'Küche',
        ]);

        $this->assertCount(0, Reinigung::upcomingForFamily($parent));
        $this->assertSame([], collect(TerminQuery::between($parent, now()->startOfDay(), now()->addMonth()))->where('source', 'reinigung')->all());

        $this->actingAs($parent)->get('/')->assertOk()->assertDontSee('Ihr Reinigungsdienst')->assertDontSee('Reinigungsdienst: Küche');
        $this->actingAs($parent)->get('termine')->assertOk()->assertDontSee('Reinigungsdienst: Küche');
    }

    /** @test */
    public function routes_are_not_reachable_when_module_is_inactive(): void
    {
        $this->deactivateReinigung();
        $admin = $this->editReinigungUser();

        $this->actingAs($admin)->get('reinigung')->assertNotFound();
        $this->actingAs($admin)->get('reinigung/Kindergarten/export')->assertNotFound();
        $this->actingAs($admin)->post('reinigung/task', ['task' => 'Küche'])->assertNotFound();

        \Laravel\Sanctum\Sanctum::actingAs($admin);
        $this->getJson('/api/v1/parent/reinigung')->assertNotFound();
    }

    /** @test */
    public function no_notifications_or_reminders_when_module_is_inactive(): void
    {
        Mail::fake();
        $this->deactivateReinigung();
        [$parent, $partner] = $this->family();
        $reinigung = Reinigung::factory()->create([
            'users_id' => $parent->id,
            'datum' => now()->addDays(3)->format('Y-m-d'),
        ]);

        app(\App\Services\Reinigung\ReinigungNotifier::class)->assigned($reinigung);

        (new ReinigungSetting)->fill(['reminder_enabled' => true, 'reminder_days_before' => 3])->save();
        (new \App\Jobs\ProcessReinigungRemindersJob)->handle();

        $this->assertSame(0, Notification::whereIn('user_id', [$parent->id, $partner->id])->count());
        Mail::assertNothingQueued();
    }

    private function deactivateReinigung(): void
    {
        Module::where('setting', 'Reinigung')->update(['options' => json_encode(['active' => '0', 'rights' => []])]);
    }

    /** @test */
    public function dashboard_shows_cleaning_widget_and_termin(): void
    {
        [$parent] = $this->family();
        $parent->update(['changePassword' => false]);
        Reinigung::factory()->create([
            'users_id' => $parent->id,
            'datum' => now()->format('Y-m-d'),
            'aufgabe' => 'Küche',
            'bemerkung' => 'Wäschebeutel mitnehmen',
        ]);

        $response = $this->actingAs($parent)->get('/');

        $response->assertOk();
        $response->assertSee('Ihr Reinigungsdienst');
        $response->assertSee('diese Woche');
        $response->assertSee('Wäschebeutel mitnehmen');
        $response->assertViewHas('termine', fn ($termine) => $termine->contains(fn ($t) => $t->terminname === 'Reinigungsdienst: Küche'));
    }

    /** @test */
    public function app_termine_include_cleaning_duties(): void
    {
        [$parent] = $this->family();
        $reinigung = Reinigung::factory()->create([
            'users_id' => $parent->id,
            'datum' => now()->addWeek()->format('Y-m-d'),
            'aufgabe' => 'Küche',
        ]);

        $entries = collect(TerminQuery::between($parent, now()->startOfDay(), now()->addMonth()));
        $entry = $entries->firstWhere('id', 'reinigung-'.$reinigung->id);

        $this->assertNotNull($entry);
        $this->assertSame('reinigung', $entry['source']);
        $this->assertTrue($entry['all_day']);
    }
}

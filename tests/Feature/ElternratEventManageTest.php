<?php

namespace Tests\Feature;

use App\Model\ElternratEvent;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Elternrat-Termine bearbeiten und löschen: Ersteller, Rolle „Elternrat“
 * und „delete elternrat file“ – andere Nutzer mit „view elternrat“ nicht.
 */
class ElternratEventManageTest extends TestCase
{
    use RefreshDatabase;

    private ElternratEvent $event;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('view elternrat');
        Permission::findOrCreate('delete elternrat file');
        Role::findOrCreate('Elternrat')->givePermissionTo('view elternrat');

        $this->creator = $this->viewer();
        $this->event = ElternratEvent::create([
            'title' => 'Sitzung',
            'description' => 'Tagesordnung',
            'start_time' => now()->addWeek()->setTime(19, 0),
            'end_time' => now()->addWeek()->setTime(21, 0),
            'location' => 'Raum 1',
            'created_by' => $this->creator->id,
            'send_reminder' => true,
            'reminder_hours' => 24,
        ]);
    }

    private function viewer(): User
    {
        $user = User::factory()->create(['changePassword' => false]);
        $user->givePermissionTo('view elternrat');

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Sitzung (verschoben)',
            'description' => 'Neue Tagesordnung',
            'start_time' => now()->addWeeks(2)->setTime(18, 30)->format('Y-m-d\TH:i'),
            'end_time' => now()->addWeeks(2)->setTime(20, 0)->format('Y-m-d\TH:i'),
            'location' => 'Aula',
            'reminder_hours' => 48,
        ], $overrides);
    }

    /** @test */
    public function creator_can_open_edit_form_and_update(): void
    {
        $this->actingAs($this->creator)
            ->get(route('elternrat.events.edit', $this->event))
            ->assertOk()
            ->assertSee('Termin bearbeiten')
            ->assertSee('Sitzung');

        $this->actingAs($this->creator)
            ->put(route('elternrat.events.update', $this->event), $this->payload())
            ->assertRedirect(route('elternrat.events.index'))
            ->assertSessionHas('Meldung', 'Termin aktualisiert');

        $event = $this->event->fresh();
        $this->assertSame('Sitzung (verschoben)', $event->title);
        $this->assertSame('Aula', $event->location);
        $this->assertSame(48, (int) $event->reminder_hours);
        $this->assertFalse($event->send_reminder, 'Checkbox nicht gesendet = Erinnerung aus');
    }

    /** @test */
    public function elternrat_role_members_can_edit_and_delete_events_of_others(): void
    {
        $member = $this->viewer();
        $member->assignRole('Elternrat');

        $this->actingAs($member)
            ->put(route('elternrat.events.update', $this->event), $this->payload(['send_reminder' => 1]))
            ->assertSessionHas('Meldung', 'Termin aktualisiert');
        $this->assertTrue($this->event->fresh()->send_reminder);

        $this->actingAs($member)
            ->delete(route('elternrat.events.destroy', $this->event))
            ->assertSessionHas('Meldung', 'Termin gelöscht');
        $this->assertModelMissing($this->event);
    }

    /** @test */
    public function other_viewers_cannot_edit_or_delete(): void
    {
        $other = $this->viewer();

        $this->actingAs($other)->get(route('elternrat.events.edit', $this->event))
            ->assertRedirect(route('elternrat.events.index'));

        $this->actingAs($other)->put(route('elternrat.events.update', $this->event), $this->payload())
            ->assertSessionHas('Meldung', 'Keine Berechtigung');
        $this->assertSame('Sitzung', $this->event->fresh()->title);

        $this->actingAs($other)->delete(route('elternrat.events.destroy', $this->event));
        $this->assertModelExists($this->event);
    }

    /** @test */
    public function creator_can_delete_own_event(): void
    {
        $this->actingAs($this->creator)
            ->delete(route('elternrat.events.destroy', $this->event))
            ->assertSessionHas('Meldung', 'Termin gelöscht');

        $this->assertModelMissing($this->event);
    }

    /** @test */
    public function index_shows_edit_button_only_for_managers(): void
    {
        $this->actingAs($this->creator)->get(route('elternrat.events.index'))
            ->assertOk()
            ->assertSee(route('elternrat.events.edit', $this->event), false);

        $this->actingAs($this->viewer())->get(route('elternrat.events.index'))
            ->assertOk()
            ->assertDontSee(route('elternrat.events.edit', $this->event), false);
    }
}

<?php

namespace Tests\Feature\Anwesenheit;

use App\Model\Child;
use App\Model\Group;
use App\Model\User;
use App\Settings\CareSetting;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Kachelansicht der Anwesenheitsliste (view_mode = board).
 */
class KachelansichtTest extends TestCase
{
    private Group $group;

    private Group $class;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = Group::factory()->create(['name' => 'Hortgruppe A']);
        $this->class = Group::factory()->create(['name' => 'Klasse 2a']);

        Permission::findOrCreate('edit schickzeiten', 'web');
        $this->staff = User::factory()->create();
        $this->staff->givePermissionTo('edit schickzeiten');
    }

    private function configure(string $mode, bool $hideAbsent = false): void
    {
        $settings = new CareSetting;
        $settings->groups_list = [$this->group->id];
        $settings->class_list = [$this->class->id];
        $settings->view_mode = $mode;
        $settings->view_detailed_care = $mode === 'detailed';
        $settings->hide_childs_when_absent = $hideAbsent;
        $settings->save();
    }

    private function child(array $attributes = []): Child
    {
        return Child::factory()->create(array_merge([
            'group_id' => $this->group->id,
            'class_id' => $this->class->id,
        ], $attributes));
    }

    #[Test]
    public function view_mode_defaults_to_previous_setting(): void
    {
        $this->assertContains((new CareSetting)->viewMode(), CareSetting::VIEW_MODES);
    }

    #[Test]
    public function board_view_shows_tiles_with_status(): void
    {
        $this->configure('board');

        $present = $this->child(['first_name' => 'Anna', 'last_name' => 'Anwesend']);
        $present->checkIns()->create(['date' => today(), 'checked_in' => true, 'checked_out' => false]);

        $gone = $this->child(['first_name' => 'Gustav', 'last_name' => 'Gegangen']);
        $gone->checkIns()->create(['date' => today(), 'checked_in' => true, 'checked_out' => true]);

        $expected = $this->child(['first_name' => 'Emil', 'last_name' => 'Erwartet']);
        $expected->checkIns()->create(['date' => today(), 'checked_in' => false, 'checked_out' => false, 'should_be' => true]);

        $html = $this->actingAs($this->staff)->get(route('anwesenheit.index'))->assertOk()->getContent();

        $this->assertStringContainsString('id="att-board"', $html);
        $this->assertStringContainsString('id="groups-container"', $html);
        $this->assertMatchesRegularExpression('/data-child-id="'.$present->id.'"\s+data-status="present"/', $html);
        $this->assertMatchesRegularExpression('/data-child-id="'.$gone->id.'"\s+data-status="gone"/', $html);
        $this->assertMatchesRegularExpression('/data-child-id="'.$expected->id.'"\s+data-status="expected"/', $html);
        // Detailfenster nutzt weiterhin data-child
        $this->assertMatchesRegularExpression("/data-child='([^']*)'/", $html);
        $this->assertStringNotContainsString('id="removeCookie"', $html);
    }

    #[Test]
    public function board_view_loads_all_children_even_when_absent_ones_are_hidden(): void
    {
        $this->configure('board', hideAbsent: true);

        $present = $this->child(['first_name' => 'Anna']);
        $present->checkIns()->create(['date' => today(), 'checked_in' => true, 'checked_out' => false]);
        $absent = $this->child(['first_name' => 'Otto']);

        $html = $this->actingAs($this->staff)->get(route('anwesenheit.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-child-id="'.$present->id.'"', $html);
        $this->assertStringContainsString('data-child-id="'.$absent->id.'"', $html);
        $this->assertStringContainsString('data-mode="present"', $html);
    }

    #[Test]
    public function simple_view_still_hides_absent_children(): void
    {
        $this->configure('simple', hideAbsent: true);

        $present = $this->child();
        $present->checkIns()->create(['date' => today(), 'checked_in' => true, 'checked_out' => false]);
        $absent = $this->child();

        $html = $this->actingAs($this->staff)->get(route('anwesenheit.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('att-board', $html);
        $this->assertStringContainsString('"id":'.$present->id.',', $html);
        $this->assertStringNotContainsString('"id":'.$absent->id.',', $html);
    }

    #[Test]
    public function device_view_route_sets_and_clears_cookie(): void
    {
        $this->configure('simple');

        $this->actingAs($this->staff)->get(route('anwesenheit.view', 'board'))
            ->assertRedirect(route('anwesenheit.index'))
            ->assertCookie(CareSetting::DEVICE_VIEW_COOKIE, 'board');

        $this->actingAs($this->staff)->get(route('anwesenheit.view', 'standard'))
            ->assertRedirect(route('anwesenheit.index'))
            ->assertCookieExpired(CareSetting::DEVICE_VIEW_COOKIE);

        $this->actingAs($this->staff)->get(url('care/anwesenheit/ansicht/unsinn'))->assertNotFound();
    }

    #[Test]
    public function device_view_overrides_setting_in_all_views(): void
    {
        $this->configure('simple');
        $this->child();

        // Ohne Gerätewahl: Einstellung, Umschalter zeigt „Standard“
        $html = $this->actingAs($this->staff)->get(route('anwesenheit.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('id="att-board"', $html);
        $this->assertStringContainsString('Standard (Einfache Liste)', $html);
        $this->assertStringContainsString(route('anwesenheit.view', 'board'), $html);

        $markers = [
            'board' => 'id="att-board"',
            'detailed' => 'id="sort-schickzeit-btn"',
            'simple' => 'custom-list-item',
        ];
        foreach ($markers as $view => $marker) {
            $html = $this->actingAs($this->staff)
                ->withCookie(CareSetting::DEVICE_VIEW_COOKIE, $view)
                ->get(route('anwesenheit.index'))->assertOk()->getContent();

            $this->assertStringContainsString($marker, $html, "Ansicht $view");
            $this->assertMatchesRegularExpression(
                '#<option value="'.preg_quote(route('anwesenheit.view', $view), '#').'"\s+selected#', $html, "Auswahl $view");
        }
    }

    #[Test]
    public function settings_page_shows_configured_view_not_device_view(): void
    {
        $this->configure('detailed');

        $settings = new CareSetting;
        $this->assertSame('detailed', $settings->configuredViewMode());

        request()->cookies->set(CareSetting::DEVICE_VIEW_COOKIE, 'board');
        $this->assertSame('board', $settings->viewMode());
        $this->assertSame('detailed', $settings->configuredViewMode());

        request()->cookies->set(CareSetting::DEVICE_VIEW_COOKIE, 'kaputt');
        $this->assertSame('detailed', $settings->viewMode());
    }

    #[Test]
    public function settings_store_view_mode(): void
    {
        Permission::findOrCreate('edit settings', 'web');
        $this->configure('simple');

        $admin = User::factory()->create();
        $admin->givePermissionTo('edit settings');

        $this->actingAs($admin)->put(url('settings/care'), [
            'view_mode' => 'board',
            'bundesland' => 'SN',
            'auto_checkin_time_schulzeit' => '07:00',
            'auto_checkin_time_ferien' => '07:00',
        ]);

        $settings = app(CareSetting::class);
        $settings->refresh();
        $this->assertSame('board', $settings->view_mode);
        $this->assertFalse($settings->view_detailed_care);
    }
}

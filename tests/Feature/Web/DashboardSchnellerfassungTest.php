<?php

namespace Tests\Feature\Web;

use App\Model\Child;
use App\Model\ChildNotice;
use App\Model\Krankmeldungen;
use App\Model\Module;
use App\Model\Schickzeiten;
use App\Model\User;
use App\Settings\SchickzeitenSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Startseite: Krankmeldung, Schickzeit und Notiz für heute direkt auf der Kinder-Karte.
 */
class DashboardSchnellerfassungTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        Permission::findOrCreate('view krankmeldung', 'web');
        Permission::findOrCreate('view schickzeiten', 'web');

        Module::create(['setting' => 'Anwesenheitsliste', 'category' => 'module', 'options' => ['active' => '1', 'rights' => []]]);
        Module::create(['setting' => 'Krankmeldung', 'category' => 'module', 'options' => ['active' => '1', 'rights' => []]]);
        Module::create(['setting' => 'Schickzeiten', 'category' => 'module', 'options' => ['active' => '1', 'rights' => []]]);

        $settings = app(SchickzeitenSetting::class);
        $settings->schicken_ab = '12:00';
        $settings->schicken_bis = '16:00';
        $settings->schicken_erlaube_zeitraum = true;
        $settings->save();

        Cache::flush();

        $this->parent = User::factory()->create(['password_changed_at' => now(), 'changePassword' => false]);
        $this->parent->givePermissionTo(['view krankmeldung', 'view schickzeiten']);
        $this->child = Child::factory()->create(['first_name' => 'Mia']);
        $this->child->parents()->attach($this->parent->id);
    }

    #[Test]
    public function card_offers_all_three_quick_actions(): void
    {
        $this->withoutVite()->actingAs($this->parent)->get('/dashboard')
            ->assertOk()
            ->assertSee('Für heute erfassen')
            ->assertSee('Krankmelden')
            ->assertSee('Schickzeit für heute')
            ->assertSee('Notiz für heute');
    }

    #[Test]
    public function quick_actions_respect_modules_and_rights(): void
    {
        $this->parent->revokePermissionTo(['view krankmeldung', 'view schickzeiten']);
        Cache::flush();

        $this->withoutVite()->actingAs($this->parent)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Krankmelden')
            ->assertDontSee('Schickzeit speichern')
            ->assertSee('Notiz für heute');
    }

    #[Test]
    public function schickzeit_for_today_is_saved_and_returns_to_dashboard(): void
    {
        $this->actingAs($this->parent)->from('/dashboard')->post(route('schickzeiten.store'), [
            'child_id' => $this->child->id,
            'specific_date' => today()->toDateString(),
            'type' => 'genau',
            'time' => '14:30',
            'redirect_back' => 1,
        ])->assertRedirect('/dashboard')->assertSessionHas('type', 'success');

        $this->assertSame(1, Schickzeiten::where('child_id', $this->child->id)->whereDate('specific_date', today())->count());
    }

    #[Test]
    public function notice_is_saved_and_removed_via_form(): void
    {
        $this->actingAs($this->parent)->from('/dashboard')->post(route('child.notice.store', $this->child), [
            'date' => today()->toDateString(),
            'notice' => 'Oma holt ab',
            'redirect_back' => 1,
        ])->assertRedirect('/dashboard')->assertSessionHas('type', 'success');

        $this->assertSame('Oma holt ab', ChildNotice::where('child_id', $this->child->id)->value('notice'));

        $this->withoutVite()->actingAs($this->parent)->get('/dashboard')->assertSee('Oma holt ab')->assertSee('Notiz ändern');

        $this->actingAs($this->parent)->from('/dashboard')->post(route('child.notice.store', $this->child), [
            'date' => today()->toDateString(),
            'notice' => '',
            'redirect_back' => 1,
        ])->assertRedirect('/dashboard');

        $this->assertSame(0, ChildNotice::where('child_id', $this->child->id)->count());
    }

    #[Test]
    public function notice_for_foreign_child_is_rejected(): void
    {
        $foreign = Child::factory()->create();

        $this->actingAs($this->parent)->from('/dashboard')->post(route('child.notice.store', $foreign), [
            'date' => today()->toDateString(),
            'notice' => 'x',
            'redirect_back' => 1,
        ])->assertSessionHas('type', 'warning');

        $this->assertSame(0, ChildNotice::count());
    }

    #[Test]
    public function krankmeldung_from_card_hides_quick_actions_for_sick_child(): void
    {
        $this->actingAs($this->parent)->from('/dashboard')->post('krankmeldung', [
            'child_id' => $this->child->id,
            'start' => today()->toDateString(),
            'ende' => today()->toDateString(),
            'kommentar' => 'Krank',
        ])->assertRedirect('/dashboard')->assertSessionHas('type', 'success');

        $this->assertSame(1, Krankmeldungen::where('child_id', $this->child->id)->count());

        Cache::flush();
        $this->withoutVite()->actingAs($this->parent)->get('/dashboard')
            ->assertSee('Krankgemeldet')
            ->assertDontSee('Krankmelden')
            ->assertDontSee('Schickzeit speichern');
    }
}

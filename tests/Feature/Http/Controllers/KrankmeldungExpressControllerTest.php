<?php

namespace Tests\Feature\Http\Controllers;

use App\Model\Child;
use App\Model\Group;
use App\Model\Krankmeldungen;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\KrankmeldungExpressController
 */
class KrankmeldungExpressControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingSekretariat(): User
    {
        $user = User::factory()->create(['password_changed_at' => now(), 'changePassword' => false]);
        Permission::firstOrCreate(['name' => 'create krankmeldung express', 'guard_name' => 'web']);
        $user->givePermissionTo('create krankmeldung express');

        return $user;
    }

    /** @test */
    public function index_returns_an_ok_response_for_authorized_user(): void
    {
        $user = $this->actingSekretariat();

        $response = $this->actingAs($user)->get(route('krankmeldung.express'));

        $response->assertOk();
        $response->assertViewIs('krankmeldung.express');
        $response->assertViewHas('krankmeldungen');
    }

    /** @test */
    public function index_shows_currently_sick_children(): void
    {
        $user = $this->actingSekretariat();
        $child = Child::factory()->create(['first_name' => 'Max', 'last_name' => 'Mustermann']);
        Krankmeldungen::factory()->create([
            'child_id' => $child->id,
            'name' => 'Max Mustermann',
            'start' => Carbon::today()->format('Y-m-d'),
            'ende' => Carbon::today()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->get(route('krankmeldung.express'));

        $response->assertOk();
        $response->assertSee('Max Mustermann');
    }

    /** @test */
    public function current_list_endpoint_returns_html_fragment_with_sick_children(): void
    {
        $user = $this->actingSekretariat();
        $child = Child::factory()->create(['first_name' => 'Max', 'last_name' => 'Mustermann']);
        Krankmeldungen::factory()->create([
            'child_id' => $child->id,
            'name' => 'Max Mustermann',
            'start' => Carbon::today()->format('Y-m-d'),
            'ende' => Carbon::today()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->get(route('krankmeldung.express.current'));

        $response->assertOk();
        $response->assertSee('Max Mustermann');
    }

    /** @test */
    public function current_list_does_not_include_past_krankmeldungen(): void
    {
        $user = $this->actingSekretariat();
        $child = Child::factory()->create(['first_name' => 'Erika', 'last_name' => 'Musterfrau']);
        Krankmeldungen::factory()->create([
            'child_id' => $child->id,
            'name' => 'Erika Musterfrau',
            'start' => Carbon::today()->subDays(5)->format('Y-m-d'),
            'ende' => Carbon::today()->subDays(3)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->get(route('krankmeldung.express.current'));

        $response->assertOk();
        $response->assertDontSee('Erika Musterfrau');
    }

    /** @test */
    public function unauthorized_user_cannot_access_express_view(): void
    {
        $user = User::factory()->create(['password_changed_at' => now(), 'changePassword' => false]);

        $response = $this->actingAs($user)->get(route('krankmeldung.express'));

        $response->assertForbidden();
    }

    /** @test */
    public function search_returns_matching_children_by_name_and_group(): void
    {
        $user = $this->actingSekretariat();

        $group = Group::factory()->create(['name' => '3b']);
        $child = Child::factory()->create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'group_id' => $group->id,
        ]);
        Child::factory()->create(['first_name' => 'Erika', 'last_name' => 'Musterfrau']);

        $response = $this->actingAs($user)->getJson(route('krankmeldung.express.search', ['q' => 'max 3b']));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $child->id, 'first_name' => 'Max']);
    }

    /** @test */
    public function search_flags_children_already_sick_today(): void
    {
        $user = $this->actingSekretariat();

        $child = Child::factory()->create(['first_name' => 'Max', 'last_name' => 'Mustermann']);
        Krankmeldungen::factory()->create([
            'child_id' => $child->id,
            'start' => Carbon::today()->format('Y-m-d'),
            'ende' => Carbon::today()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->getJson(route('krankmeldung.express.search', ['q' => 'Max']));

        $response->assertOk();
        $response->assertJsonFragment(['sick_today' => true]);
    }

    /** @test */
    public function store_creates_krankmeldung_for_selected_child(): void
    {
        $user = $this->actingSekretariat();
        $child = Child::factory()->create(['first_name' => 'Max', 'last_name' => 'Mustermann']);

        $response = $this->actingAs($user)->postJson(route('krankmeldung.express.store'), [
            'child_id' => $child->id,
            'start' => Carbon::today()->format('Y-m-d'),
            'ende' => Carbon::today()->format('Y-m-d'),
            'kommentar' => 'Fieber',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('krankmeldungen', [
            'child_id' => $child->id,
            'users_id' => $user->id,
        ]);
    }

    /** @test */
    public function store_rejects_duplicate_krankmeldung_for_overlapping_period(): void
    {
        $user = $this->actingSekretariat();
        $child = Child::factory()->create(['first_name' => 'Max', 'last_name' => 'Mustermann']);

        Krankmeldungen::factory()->create([
            'child_id' => $child->id,
            'start' => Carbon::today()->format('Y-m-d'),
            'ende' => Carbon::today()->addDays(2)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->postJson(route('krankmeldung.express.store'), [
            'child_id' => $child->id,
            'start' => Carbon::today()->format('Y-m-d'),
            'ende' => Carbon::today()->format('Y-m-d'),
        ]);

        $response->assertStatus(409);
    }

    /** @test */
    public function store_does_not_send_any_mail(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $user = $this->actingSekretariat();
        $child = Child::factory()->create(['first_name' => 'Max', 'last_name' => 'Mustermann']);

        $this->actingAs($user)->postJson(route('krankmeldung.express.store'), [
            'child_id' => $child->id,
            'start' => Carbon::today()->format('Y-m-d'),
            'ende' => Carbon::today()->format('Y-m-d'),
        ])->assertCreated();

        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    /** @test */
    public function unauthenticated_user_cannot_access_express_routes(): void
    {
        $response = $this->get(route('krankmeldung.express'));

        $response->assertRedirect(route('login'));
    }
}

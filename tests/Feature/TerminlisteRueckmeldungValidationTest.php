<?php

namespace Tests\Feature;

use App\Model\Group;
use App\Model\Liste;
use App\Model\listen_termine;
use App\Model\Post;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TerminlisteRueckmeldungValidationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected Liste $liste;

    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'create posts', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('create posts');

        $group = Group::factory()->create();
        $this->user->groups()->attach($group);

        $this->liste = Liste::factory()->create([
            'type' => 'termin',
            'active' => 1,
            'besitzer' => $this->user->id,
            'ende' => Carbon::now()->addMonths(2),
        ]);
        $this->liste->groups()->attach($group);

        listen_termine::factory()->create([
            'listen_id' => $this->liste->id,
            'termin' => Carbon::now()->addDays(5),
            'reserviert_fuer' => null,
        ]);

        $this->post = Post::factory()->create([
            'author' => $this->user->id,
            'released' => 1,
        ]);
        $this->post->groups()->attach($group);
    }

    /** @test */
    public function liste_id_is_required()
    {
        $response = $this->actingAs($this->user)
            ->post("/rueckmeldung/{$this->post->id}/create/terminliste", [
                'terminliste_start_date' => Carbon::today()->format('Y-m-d'),
                'terminliste_end_date' => Carbon::today()->addWeek()->format('Y-m-d'),
                'ende' => Carbon::now()->addWeek()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('liste_id');
    }

    /** @test */
    public function liste_id_must_exist()
    {
        $response = $this->actingAs($this->user)
            ->post("/rueckmeldung/{$this->post->id}/create/terminliste", [
                'liste_id' => 99999,
                'terminliste_start_date' => Carbon::today()->format('Y-m-d'),
                'terminliste_end_date' => Carbon::today()->addWeek()->format('Y-m-d'),
                'ende' => Carbon::now()->addWeek()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('liste_id');
    }

    /** @test */
    public function start_date_is_required()
    {
        $response = $this->actingAs($this->user)
            ->post("/rueckmeldung/{$this->post->id}/create/terminliste", [
                'liste_id' => $this->liste->id,
                'terminliste_end_date' => Carbon::today()->addWeek()->format('Y-m-d'),
                'ende' => Carbon::now()->addWeek()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('terminliste_start_date');
    }

    /** @test */
    public function end_date_is_required()
    {
        $response = $this->actingAs($this->user)
            ->post("/rueckmeldung/{$this->post->id}/create/terminliste", [
                'liste_id' => $this->liste->id,
                'terminliste_start_date' => Carbon::today()->format('Y-m-d'),
                'ende' => Carbon::now()->addWeek()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('terminliste_end_date');
    }

    /** @test */
    public function start_date_must_be_today_or_future()
    {
        $response = $this->actingAs($this->user)
            ->post("/rueckmeldung/{$this->post->id}/create/terminliste", [
                'liste_id' => $this->liste->id,
                'terminliste_start_date' => Carbon::yesterday()->format('Y-m-d'),
                'terminliste_end_date' => Carbon::today()->addWeek()->format('Y-m-d'),
                'ende' => Carbon::now()->addWeek()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('terminliste_start_date');
    }

    /** @test */
    public function end_date_cannot_be_before_start_date()
    {
        $response = $this->actingAs($this->user)
            ->post("/rueckmeldung/{$this->post->id}/create/terminliste", [
                'liste_id' => $this->liste->id,
                'terminliste_start_date' => Carbon::today()->addWeek()->format('Y-m-d'),
                'terminliste_end_date' => Carbon::today()->format('Y-m-d'),
                'ende' => Carbon::now()->addWeek()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('terminliste_end_date');
    }

    /** @test */
    public function buchungsfrist_is_required()
    {
        $response = $this->actingAs($this->user)
            ->post("/rueckmeldung/{$this->post->id}/create/terminliste", [
                'liste_id' => $this->liste->id,
                'terminliste_start_date' => Carbon::today()->format('Y-m-d'),
                'terminliste_end_date' => Carbon::today()->addWeek()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('ende');
    }

    /** @test */
    public function pflicht_must_be_0_or_1()
    {
        $response = $this->actingAs($this->user)
            ->post("/rueckmeldung/{$this->post->id}/create/terminliste", [
                'liste_id' => $this->liste->id,
                'terminliste_start_date' => Carbon::today()->format('Y-m-d'),
                'terminliste_end_date' => Carbon::today()->addWeek()->format('Y-m-d'),
                'ende' => Carbon::now()->addWeek()->format('Y-m-d'),
                'pflicht' => 5,
            ]);

        $response->assertSessionHasErrors('pflicht');
    }

    /** @test */
    public function valid_data_creates_rueckmeldung()
    {
        $response = $this->actingAs($this->user)
            ->post("/rueckmeldung/{$this->post->id}/create/terminliste", [
                'liste_id' => $this->liste->id,
                'terminliste_start_date' => Carbon::today()->format('Y-m-d'),
                'terminliste_end_date' => Carbon::today()->addWeek()->format('Y-m-d'),
                'ende' => Carbon::now()->addWeek()->format('Y-m-d'),
                'pflicht' => 1,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function same_start_and_end_date_is_valid()
    {
        $date = Carbon::today();

        $response = $this->actingAs($this->user)
            ->post("/rueckmeldung/{$this->post->id}/create/terminliste", [
                'liste_id' => $this->liste->id,
                'terminliste_start_date' => $date->format('Y-m-d'),
                'terminliste_end_date' => $date->format('Y-m-d'),
                'ende' => Carbon::now()->addWeek()->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function empfaenger_must_be_valid_email_if_provided()
    {
        $response = $this->actingAs($this->user)
            ->post("/rueckmeldung/{$this->post->id}/create/terminliste", [
                'liste_id' => $this->liste->id,
                'terminliste_start_date' => Carbon::today()->format('Y-m-d'),
                'terminliste_end_date' => Carbon::today()->addWeek()->format('Y-m-d'),
                'ende' => Carbon::now()->addWeek()->format('Y-m-d'),
                'empfaenger' => 'invalid-email',
            ]);

        $response->assertSessionHasErrors('empfaenger');
    }

    /** @test */
    public function user_without_permission_cannot_create_terminliste_rueckmeldung()
    {
        $unauthorizedUser = User::factory()->create();

        $response = $this->actingAs($unauthorizedUser)
            ->post("/rueckmeldung/{$this->post->id}/create/terminliste", [
                'liste_id' => $this->liste->id,
                'terminliste_start_date' => Carbon::today()->format('Y-m-d'),
                'terminliste_end_date' => Carbon::today()->addWeek()->format('Y-m-d'),
                'ende' => Carbon::now()->addWeek()->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'warning');
    }
}

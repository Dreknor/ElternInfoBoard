<?php

namespace Tests\Feature;

use App\Model\Liste;
use App\Model\listen_termine;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TerminlistenAnsichtTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'edit terminliste', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'changePassword' => false,
            'password_changed_at' => now(),
        ]);
        $this->admin->givePermissionTo('edit terminliste');
    }

    public function test_shows_hint_when_all_termine_are_in_the_past(): void
    {
        $liste = Liste::factory()->create([
            'type' => 'termin',
            'besitzer' => $this->admin->id,
        ]);

        listen_termine::factory()->count(2)->create([
            'listen_id' => $liste->id,
            'termin' => Carbon::now()->subDays(2),
            'reserviert_fuer' => null,
        ]);

        $response = $this->actingAs($this->admin)->get("/listen/{$liste->id}");

        $response->assertOk();
        $response->assertSee('Alle angelegten Termine liegen bereits in der Vergangenheit.');
        $response->assertSee('Mit „Alle“ kannst du sie trotzdem einblenden.');
    }

    public function test_does_not_show_hint_when_future_termine_exist(): void
    {
        $liste = Liste::factory()->create([
            'type' => 'termin',
            'besitzer' => $this->admin->id,
        ]);

        listen_termine::factory()->create([
            'listen_id' => $liste->id,
            'termin' => Carbon::now()->subDay(),
            'reserviert_fuer' => null,
        ]);

        listen_termine::factory()->create([
            'listen_id' => $liste->id,
            'termin' => Carbon::now()->addDay(),
            'reserviert_fuer' => null,
        ]);

        $response = $this->actingAs($this->admin)->get("/listen/{$liste->id}");

        $response->assertOk();
        $response->assertDontSee('Alle angelegten Termine liegen bereits in der Vergangenheit.');
    }
}

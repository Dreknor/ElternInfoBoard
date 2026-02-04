<?php

namespace Tests\Feature\Http\Controllers;

use App\Model\ActiveDisease;
use App\Model\Disease;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ActiveDiseaseController
 */
class ActiveDiseaseControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function authorized_user_can_view_create_disease_form(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        Permission::create(['name' => 'manage diseases']);
        $user->givePermissionTo('manage diseases');

        $response = $this->actingAs($user)->get(route('active-diseases.create'));

        $response->assertStatus(200);
        $response->assertViewIs('krankmeldung.createDisease');
    }

    /**
     * @test
     */
    public function authorized_user_can_create_active_disease(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        Permission::create(['name' => 'manage diseases']);
        $user->givePermissionTo('manage diseases');

        $disease = Disease::factory()->create();

        $response = $this->actingAs($user)->post(route('active-diseases.store'), [
            'disease_id' => $disease->id,
            'start' => now()->format('Y-m-d'),
            'end' => now()->addDays(7)->format('Y-m-d'),
            'comment' => 'Test Kommentar',
            'active' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('active_diseases', [
            'disease_id' => $disease->id,
            'user_id' => $user->id,
            'active' => false,
        ]);
    }

    /**
     * @test
     */
    public function authorized_user_can_deactivate_disease(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        Permission::create(['name' => 'manage diseases']);
        $user->givePermissionTo('manage diseases');

        $activeDisease = ActiveDisease::factory()->active()->create();

        $response = $this->actingAs($user)->put("/diseases/{$activeDisease->id}/active", [
            'active' => false,
        ]);

        $response->assertRedirect();

        $activeDisease->refresh();
        $this->assertEquals(0, $activeDisease->active);
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_manage_diseases(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        $disease = Disease::factory()->create();

        $response = $this->actingAs($user)->post(route('active-diseases.store'), [
            'disease_id' => $disease->id,
            'start' => now()->format('Y-m-d'),
            'end' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function active_diseases_can_be_created_and_retrieved(): void
    {
        $user = User::factory()->create();

        $activeDiseases = ActiveDisease::factory()->count(2)->active()->create();
        $inactiveDisease = ActiveDisease::factory()->inactive()->create();

        $activeCount = ActiveDisease::where('active', true)->count();

        $this->assertEquals(2, $activeCount);
    }
}

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
    public function authorized_user_can_view_active_diseases()
    {
        $user = User::factory()->create();
        Permission::create(['name' => 'view diseases']);
        $user->givePermissionTo('view diseases');

        ActiveDisease::factory()->count(3)->active()->create();

        $response = $this->actingAs($user)->get(route('diseases.index'));

        $response->assertOk();
        $response->assertViewHas('diseases');
    }

    /**
     * @test
     */
    public function authorized_user_can_create_active_disease()
    {
        $user = User::factory()->create();
        Permission::create(['name' => 'manage diseases']);
        $user->givePermissionTo('manage diseases');

        $disease = Disease::factory()->create();

        $response = $this->actingAs($user)->post(route('diseases.store'), [
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
            'active' => true,
        ]);
    }

    /**
     * @test
     */
    public function authorized_user_can_deactivate_disease()
    {
        $user = User::factory()->create();
        Permission::create(['name' => 'manage diseases']);
        $user->givePermissionTo('manage diseases');

        $activeDisease = ActiveDisease::factory()->active()->create();

        $response = $this->actingAs($user)->put(route('diseases.update', $activeDisease), [
            'active' => false,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('active_diseases', [
            'id' => $activeDisease->id,
            'active' => false,
        ]);
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_manage_diseases()
    {
        $user = User::factory()->create();
        $disease = Disease::factory()->create();

        $response = $this->actingAs($user)->post(route('diseases.store'), [
            'disease_id' => $disease->id,
            'start' => now()->format('Y-m-d'),
            'end' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $response->assertForbidden();
    }

    /**
     * @test
     */
    public function active_diseases_are_displayed_correctly()
    {
        $user = User::factory()->create();
        Permission::create(['name' => 'view diseases']);
        $user->givePermissionTo('view diseases');

        $activeDiseases = ActiveDisease::factory()->count(2)->active()->create();
        $inactiveDisease = ActiveDisease::factory()->inactive()->create();

        $response = $this->actingAs($user)->get(route('diseases.index'));

        $response->assertOk();
        $viewData = $response->viewData('diseases');

        $this->assertCount(2, $viewData->where('active', true));
    }
}


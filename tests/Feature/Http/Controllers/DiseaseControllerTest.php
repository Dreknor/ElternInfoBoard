<?php

namespace Tests\Feature\Http\Controllers;

use App\Model\Disease;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\DiseaseController
 */
class DiseaseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function createAuthorizedUser()
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        Permission::firstOrCreate(['name' => 'manage diseases']);
        $user->givePermissionTo('manage diseases');
        return $user;
    }

    /**
     * @test
     */
    public function authorized_user_can_view_diseases_index()
    {
        $user = $this->createAuthorizedUser();
        Disease::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('diseases.index'));

        $response->assertStatus(200);
        $response->assertViewIs('krankmeldung.diseases.manage');
        $response->assertViewHas('diseases');
        $response->assertViewHas('activeDiseases');
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_view_diseases_index()
    {
        $user = User::factory()->create(['password_changed_at' => now()]);

        $response = $this->actingAs($user)->get(route('diseases.index'));

        $response->assertStatus(403);
    }


    /**
     * @test
     */
    public function authorized_user_can_create_disease()
    {
        $user = $this->createAuthorizedUser();

        $diseaseData = [
            'name' => 'Test Krankheit',
            'reporting' => true,
            'wiederzulassung_durch' => 'Ärztliches Attest',
            'wiederzulassung_wann' => 'Nach 24h Symptomfreiheit',
            'aushang_dauer' => 14,
        ];

        $response = $this->actingAs($user)->post(route('diseases.store'), $diseaseData);

        $response->assertRedirect(route('diseases.index'));
        $this->assertDatabaseHas('diseases', ['name' => 'Test Krankheit']);
    }

    /**
     * @test
     */
    public function disease_name_must_be_unique()
    {
        $user = $this->createAuthorizedUser();
        Disease::factory()->create(['name' => 'Existing Disease']);

        $diseaseData = [
            'name' => 'Existing Disease',
            'reporting' => true,
            'wiederzulassung_durch' => 'Ärztliches Attest',
            'wiederzulassung_wann' => 'Nach 24h Symptomfreiheit',
            'aushang_dauer' => 14,
        ];

        $response = $this->actingAs($user)->post(route('diseases.store'), $diseaseData);

        $response->assertSessionHasErrors('name');
    }


    /**
     * @test
     */
    public function authorized_user_can_update_disease()
    {
        $user = $this->createAuthorizedUser();
        $disease = Disease::factory()->create();

        $updatedData = [
            'name' => 'Updated Disease Name',
            'reporting' => false,
            'wiederzulassung_durch' => 'Updated Attest',
            'wiederzulassung_wann' => 'Updated Bedingung',
            'aushang_dauer' => 21,
        ];

        $response = $this->actingAs($user)->put(route('diseases.update', $disease->id), $updatedData);

        $response->assertRedirect(route('diseases.index'));
        $this->assertDatabaseHas('diseases', [
            'id' => $disease->id,
            'name' => 'Updated Disease Name',
            'aushang_dauer' => 21,
        ]);
    }

    /**
     * @test
     */
    public function authorized_user_can_delete_disease_without_active_diseases()
    {
        $user = $this->createAuthorizedUser();
        $disease = Disease::factory()->create();

        $response = $this->actingAs($user)->delete(route('diseases.destroy', $disease->id));

        $response->assertRedirect(route('diseases.index'));
        $this->assertDatabaseMissing('diseases', ['id' => $disease->id]);
    }

    /**
     * @test
     */
    public function cannot_delete_disease_with_active_diseases()
    {
        $user = $this->createAuthorizedUser();
        $disease = Disease::factory()->create();

        // Create an active disease using the disease
        $disease->activeDiseases()->create([
            'user_id' => $user->id,
            'start' => now(),
            'end' => now()->addDays(14),
            'active' => true,
        ]);

        $response = $this->actingAs($user)->delete(route('diseases.destroy', $disease->id));

        $response->assertSessionHas('type', 'danger');
        $this->assertDatabaseHas('diseases', ['id' => $disease->id]);
    }
}


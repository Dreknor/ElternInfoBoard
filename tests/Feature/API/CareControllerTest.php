<?php

namespace Tests\Feature\API;

use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\Disease;
use App\Model\Group;
use App\Model\Krankmeldungen;
use App\Model\User;
use App\Settings\CareSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CareControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private CareSetting $careSettings;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user with the required permission
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('edit schickzeiten');

        // Setup care settings
        $this->careSettings = new CareSetting();
        $this->careSettings->groups_list = [1];
        $this->careSettings->class_list = [1];
        $this->careSettings->save();
    }

    /** @test */
    public function it_requires_authentication_to_access_present_children()
    {
        $response = $this->getJson('/api/care/present');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_permission_to_access_present_children()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/care/present');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Sie haben keine Berechtigung für diese Aktion.',
            ]);
    }

    /** @test */
    public function it_returns_present_children()
    {
        Sanctum::actingAs($this->user);

        $group = Group::factory()->create(['id' => 1]);
        $child = Child::factory()->create([
            'group_id' => 1,
            'class_id' => 1,
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
        ]);

        ChildCheckIn::create([
            'child_id' => $child->id,
            'checked_in' => true,
            'checked_out' => false,
            'date' => now()->toDateString(),
        ]);

        $response = $this->getJson('/api/care/present');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 1,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'first_name',
                        'last_name',
                        'group_id',
                        'group',
                        'class_id',
                        'class',
                        'checked_in_at',
                        'is_sick',
                    ],
                ],
                'count',
            ]);

        $this->assertEquals('Max', $response->json('data.0.first_name'));
        $this->assertEquals('Mustermann', $response->json('data.0.last_name'));
    }

    /** @test */
    public function it_requires_authentication_to_access_sick_children()
    {
        $response = $this->getJson('/api/care/sick');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_permission_to_access_sick_children()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/care/sick');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Sie haben keine Berechtigung für diese Aktion.',
            ]);
    }

    /** @test */
    public function it_returns_sick_children()
    {
        Sanctum::actingAs($this->user);

        $group = Group::factory()->create(['id' => 1]);
        $child = Child::factory()->create([
            'group_id' => 1,
            'class_id' => 1,
            'first_name' => 'Anna',
            'last_name' => 'Schmidt',
        ]);

        $disease = Disease::factory()->create(['name' => 'Influenza']);

        Krankmeldungen::create([
            'child_id' => $child->id,
            'users_id' => $this->user->id,
            'name' => 'Anna Schmidt',
            'kommentar' => 'Grippe mit Fieber',
            'start' => today(),
            'ende' => today()->addDays(3),
            'disease_id' => $disease->id,
        ]);

        $response = $this->getJson('/api/care/sick');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 1,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'first_name',
                        'last_name',
                        'group_id',
                        'group',
                        'class_id',
                        'class',
                        'krankmeldung' => [
                            'id',
                            'name',
                            'kommentar',
                            'start',
                            'ende',
                            'disease',
                        ],
                    ],
                ],
                'count',
            ]);

        $this->assertEquals('Anna', $response->json('data.0.first_name'));
        $this->assertEquals('Schmidt', $response->json('data.0.last_name'));
        $this->assertEquals('Influenza', $response->json('data.0.krankmeldung.disease.name'));
    }

    /** @test */
    public function it_requires_authentication_to_access_care_overview()
    {
        $response = $this->getJson('/api/care/overview');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_permission_to_access_care_overview()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/care/overview');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Sie haben keine Berechtigung für diese Aktion.',
            ]);
    }

    /** @test */
    public function it_returns_care_overview()
    {
        Sanctum::actingAs($this->user);

        $group = Group::factory()->create(['id' => 1]);

        // Create present child
        $presentChild = Child::factory()->create([
            'group_id' => 1,
            'class_id' => 1,
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
        ]);

        ChildCheckIn::create([
            'child_id' => $presentChild->id,
            'checked_in' => true,
            'checked_out' => false,
            'date' => now()->toDateString(),
        ]);

        // Create sick child
        $sickChild = Child::factory()->create([
            'group_id' => 1,
            'class_id' => 1,
            'first_name' => 'Anna',
            'last_name' => 'Schmidt',
        ]);

        Krankmeldungen::create([
            'child_id' => $sickChild->id,
            'users_id' => $this->user->id,
            'name' => 'Anna Schmidt',
            'kommentar' => 'Grippe',
            'start' => today(),
            'ende' => today()->addDays(3),
        ]);

        $response = $this->getJson('/api/care/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'present_children',
                    'present_count',
                    'sick_children',
                    'sick_count',
                    'timestamp',
                ],
            ]);

        $this->assertEquals(1, $response->json('data.present_count'));
        $this->assertEquals(1, $response->json('data.sick_count'));
    }

    /** @test */
    public function it_excludes_checked_out_children_from_present_list()
    {
        Sanctum::actingAs($this->user);

        $group = Group::factory()->create(['id' => 1]);
        $child = Child::factory()->create([
            'group_id' => 1,
            'class_id' => 1,
        ]);

        ChildCheckIn::create([
            'child_id' => $child->id,
            'checked_in' => true,
            'checked_out' => true, // Already checked out
            'date' => now()->toDateString(),
        ]);

        $response = $this->getJson('/api/care/present');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 0,
                'data' => [],
            ]);
    }

    /** @test */
    public function it_excludes_expired_sick_reports_from_sick_list()
    {
        Sanctum::actingAs($this->user);

        $group = Group::factory()->create(['id' => 1]);
        $child = Child::factory()->create([
            'group_id' => 1,
            'class_id' => 1,
        ]);

        // Create an expired sick report
        Krankmeldungen::create([
            'child_id' => $child->id,
            'users_id' => $this->user->id,
            'name' => 'Test',
            'kommentar' => 'Test',
            'start' => today()->subDays(5),
            'ende' => today()->subDays(2), // Ended 2 days ago
        ]);

        $response = $this->getJson('/api/care/sick');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 0,
                'data' => [],
            ]);
    }
}


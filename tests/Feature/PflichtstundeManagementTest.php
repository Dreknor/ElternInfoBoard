<?php

namespace Tests\Feature;

use App\Model\Pflichtstunde;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-Tests für Pflichtstunden-Management
 */
class PflichtstundeManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function user_can_create_pflichtstunde()
    {
        $user = User::factory()->create();

        $pflichtstunde = Pflichtstunde::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('pflichtstunden', [
            'id' => $pflichtstunde->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * @test
     */
    public function pflichtstunde_can_be_approved()
    {
        $user = User::factory()->create();
        $approver = User::factory()->create();
        $pflichtstunde = Pflichtstunde::factory()->pending()->create(['user_id' => $user->id]);

        $pflichtstunde->update([
            'approved' => true,
            'approved_at' => now(),
            'approved_by' => $approver->id,
        ]);

        $this->assertTrue($pflichtstunde->approved);
        $this->assertNotNull($pflichtstunde->approved_at);
        $this->assertEquals($approver->id, $pflichtstunde->approved_by);
    }

    /**
     * @test
     */
    public function pflichtstunde_can_be_rejected()
    {
        $user = User::factory()->create();
        $rejecter = User::factory()->create();
        $pflichtstunde = Pflichtstunde::factory()->pending()->create(['user_id' => $user->id]);

        $pflichtstunde->update([
            'rejected' => true,
            'rejected_at' => now(),
            'rejected_by' => $rejecter->id,
            'rejection_reason' => 'Nicht ausreichend dokumentiert',
        ]);

        $this->assertTrue($pflichtstunde->rejected);
        $this->assertNotNull($pflichtstunde->rejected_at);
        $this->assertEquals('Nicht ausreichend dokumentiert', $pflichtstunde->rejection_reason);
    }

    /**
     * @test
     */
    public function pflichtstunde_belongs_to_user()
    {
        $user = User::factory()->create();
        $pflichtstunde = Pflichtstunde::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $pflichtstunde->user);
        $this->assertEquals($user->id, $pflichtstunde->user->id);
    }

    /**
     * @test
     */
    public function pflichtstunde_has_approver_relation()
    {
        $approver = User::factory()->create();
        $pflichtstunde = Pflichtstunde::factory()->approved()->create([
            'approved_by' => $approver->id,
        ]);

        $this->assertInstanceOf(User::class, $pflichtstunde->approver);
        $this->assertEquals($approver->id, $pflichtstunde->approver->id);
    }

    /**
     * @test
     */
    public function pflichtstunde_duration_is_calculated_correctly()
    {
        $start = now();
        $end = now()->addHours(3);

        $pflichtstunde = Pflichtstunde::factory()->create([
            'start' => $start,
            'end' => $end,
        ]);

        $duration = $pflichtstunde->end->diffInHours($pflichtstunde->start);
        $this->assertEquals(3, $duration);
    }

    /**
     * @test
     */
    public function soft_deleted_pflichtstunde_can_be_restored()
    {
        $pflichtstunde = Pflichtstunde::factory()->create();

        $pflichtstunde->delete();
        $this->assertSoftDeleted('pflichtstunden', ['id' => $pflichtstunde->id]);

        $pflichtstunde->restore();
        $this->assertDatabaseHas('pflichtstunden', ['id' => $pflichtstunde->id]);
    }
}

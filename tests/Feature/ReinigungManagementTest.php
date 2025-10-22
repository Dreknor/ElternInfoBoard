<?php

namespace Tests\Feature;

use App\Model\Reinigung;
use App\Model\ReinigungsTask;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-Tests für Reinigungsplan-Management
 */
class ReinigungManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function user_can_be_assigned_to_reinigung()
    {
        $user = User::factory()->create();

        $reinigung = Reinigung::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addWeek(),
        ]);

        $this->assertDatabaseHas('reiniguns', [
            'id' => $reinigung->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * @test
     */
    public function reinigung_belongs_to_user()
    {
        $user = User::factory()->create();
        $reinigung = Reinigung::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $reinigung->user);
        $this->assertEquals($user->id, $reinigung->user->id);
    }

    /**
     * @test
     */
    public function reinigung_can_have_tasks()
    {
        $reinigung = Reinigung::factory()->create();
        $tasks = ReinigungsTask::factory()->count(5)->create([
            'reinigung_id' => $reinigung->id,
        ]);

        $this->assertCount(5, $reinigung->tasks);
    }

    /**
     * @test
     */
    public function reinigungstask_can_be_marked_as_completed()
    {
        $task = ReinigungsTask::factory()->create([
            'completed' => false,
        ]);

        $task->update(['completed' => true]);

        $this->assertDatabaseHas('reinigungs_tasks', [
            'id' => $task->id,
            'completed' => true,
        ]);
    }

    /**
     * @test
     */
    public function user_can_see_upcoming_reinigung_assignments()
    {
        $user = User::factory()->create();

        Reinigung::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addDays(5),
        ]);

        Reinigung::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDays(5),
        ]);

        $upcomingReinigungen = Reinigung::where('user_id', $user->id)
            ->where('date', '>', now())
            ->get();

        $this->assertCount(1, $upcomingReinigungen);
    }
}



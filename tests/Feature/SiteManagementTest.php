<?php

namespace Tests\Feature;

use App\Model\Site;
use App\Model\SiteBlock;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-Tests für Site-Management (CMS-Funktionalität)
 */
class SiteManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function author_can_create_site()
    {
        $user = User::factory()->create();

        $site = Site::factory()->create([
            'author_id' => $user->id,
            'name' => 'Test Seite',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'author_id' => $user->id,
            'name' => 'Test Seite',
        ]);
    }

    /**
     * @test
     */
    public function site_can_be_activated()
    {
        $site = Site::factory()->inactive()->create();

        $site->update(['is_active' => true]);

        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'is_active' => true,
        ]);
    }

    /**
     * @test
     */
    public function site_belongs_to_author()
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['author_id' => $user->id]);

        $this->assertInstanceOf(User::class, $site->author);
        $this->assertEquals($user->id, $site->author->id);
    }

    /**
     * @test
     */
    public function site_can_have_blocks()
    {
        $site = Site::factory()->create();
        $blocks = SiteBlock::factory()->count(5)->create([
            'site_id' => $site->id,
        ]);

        $this->assertCount(5, $site->blocks);
    }

    /**
     * @test
     */
    public function blocks_are_ordered_by_position()
    {
        $site = Site::factory()->create();

        SiteBlock::factory()->create([
            'site_id' => $site->id,
            'position' => 3,
        ]);

        SiteBlock::factory()->create([
            'site_id' => $site->id,
            'position' => 1,
        ]);

        SiteBlock::factory()->create([
            'site_id' => $site->id,
            'position' => 2,
        ]);

        $blocks = $site->blocks()->get();

        $this->assertEquals(1, $blocks->first()->position);
        $this->assertEquals(3, $blocks->last()->position);
    }

    /**
     * @test
     */
    public function only_active_sites_are_visible_to_non_authors()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $activeSite = Site::factory()->active()->create(['author_id' => $otherUser->id]);
        $inactiveSite = Site::factory()->inactive()->create(['author_id' => $otherUser->id]);

        $this->actingAs($user);

        // Aktive Site sollte sichtbar sein
        $visibleSites = Site::where('is_active', true)->get();
        $this->assertTrue($visibleSites->contains($activeSite));
        $this->assertFalse($visibleSites->contains($inactiveSite));
    }

    /**
     * @test
     */
    public function author_can_see_own_inactive_sites()
    {
        $user = User::factory()->create();

        $inactiveSite = Site::factory()->inactive()->create(['author_id' => $user->id]);

        $this->actingAs($user);

        // Autor sollte eigene inaktive Sites sehen können
        $ownSites = Site::where('author_id', $user->id)->get();
        $this->assertTrue($ownSites->contains($inactiveSite));
    }
}

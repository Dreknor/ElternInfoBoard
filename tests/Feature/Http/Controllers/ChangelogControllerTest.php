<?php

namespace Tests\Feature\Http\Controllers;

use App\Model\Changelog;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ChangelogController
 */
class ChangelogControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function index_returns_an_ok_response(): void
    {
        $user = User::factory()->create(['changePassword' => false]);
        Changelog::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('changelog.index'));

        $response->assertOk();
        $response->assertViewIs('changelog.index');
        $response->assertViewHas('changelogs');
    }

    /**
     * @test
     */
    public function index_displays_paginated_changelogs(): void
    {
        $user = User::factory()->create(['changePassword' => false]);
        Changelog::factory()->count(10)->create();

        $response = $this->actingAs($user)->get(route('changelog.index'));

        $response->assertOk();
        $changelogs = $response->viewData('changelogs');
        $this->assertEquals(5, $changelogs->perPage());
    }

    /**
     * @test
     */
    public function create_returns_an_ok_response_for_authorized_user(): void
    {
        $user = User::factory()->create(['changePassword' => false]);
        Permission::create(['name' => 'add changelog']);
        $user->givePermissionTo('add changelog');

        $response = $this->actingAs($user)->get(route('changelog.create'));

        $response->assertOk();
        $response->assertViewIs('changelog.create');
    }

    /**
     * @test
     */
    public function create_redirects_for_unauthorized_user(): void
    {
        $user = User::factory()->create(['changePassword' => false]);

        $response = $this->actingAs($user)->get(route('changelog.create'));

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function store_creates_new_changelog(): void
    {
        $user = User::factory()->create(['changePassword' => false]);
        Permission::create(['name' => 'add changelog']);
        $user->givePermissionTo('add changelog');

        $response = $this->actingAs($user)->post(route('changelog.store'), [
            'header' => 'Test Changelog Header',
            'text' => 'This is a test changelog text.',
            'changeSettings' => false,
        ]);

        $response->assertRedirect(url('changelog'));
        $response->assertSessionHas('type', 'success');

        $this->assertDatabaseHas('changelogs', [
            'header' => 'Test Changelog Header',
            'text' => 'This is a test changelog text.',
        ]);
    }

    /**
     * @test
     */
    public function store_updates_user_change_settings_when_flag_is_set(): void
    {
        $user = User::factory()->create(['changeSettings' => false]);
        Permission::create(['name' => 'add changelog']);
        $user->givePermissionTo('add changelog');

        $response = $this->actingAs($user)->post(route('changelog.store'), [
            'header' => 'Important Update',
            'text' => 'Please review your settings.',
            'changeSettings' => true,
        ]);

        $response->assertRedirect(url('changelog'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'changeSettings' => 1,
        ]);
    }

    /**
     * @test
     */
    public function store_validates_with_a_form_request(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ChangelogController::class,
            'store',
            \App\Http\Requests\CreateChangelogRequest::class
        );
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_access_changelog_index(): void
    {
        $response = $this->get(route('changelog.index'));

        $response->assertRedirect(route('login'));
    }
}

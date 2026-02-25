<?php

namespace Tests\Feature\API;

use App\Model\User;
use App\Model\UserAppSettings;
use App\Services\UserAppSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    public function setUp(): void
    {
        parent::setUp();
        /** @var User $user */
        $user = User::factory()->create();
        $this->user = $user;
    }

    /**
     * Test getting settings when none exist.
     */
    public function test_get_settings_returns_404_when_none_exist(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/user/settings');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'No settings found. Using defaults.',
            ]);
    }

    /**
     * Test getting default settings.
     */
    public function test_get_default_settings(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/user/settings/default');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'settings' => UserAppSettingsService::getDefaultSettings(),
                ],
            ]);
    }

    /**
     * Test creating settings.
     */
    public function test_create_settings(): void
    {
        $settings = UserAppSettingsService::getDefaultSettings();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user/settings', [
                'settings' => $settings,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Settings saved successfully',
            ]);

        $this->assertDatabaseHas('user_app_settings', [
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test updating settings.
     */
    public function test_update_settings(): void
    {
        // Create initial settings
        $settings = UserAppSettingsService::getDefaultSettings();
        UserAppSettings::create([
            'user_id' => $this->user->id,
            'settings' => $settings,
        ]);

        // Update theme mode
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson('/api/user/settings', [
                'path' => 'theme.mode',
                'value' => 'dark',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Settings updated successfully',
            ]);

        $userSettings = UserAppSettings::where('user_id', $this->user->id)->first();
        $this->assertEquals('dark', $userSettings->settings['theme']['mode']);
    }

    /**
     * Test updating settings with invalid path.
     */
    public function test_update_settings_with_invalid_path(): void
    {
        $settings = UserAppSettingsService::getDefaultSettings();
        UserAppSettings::create([
            'user_id' => $this->user->id,
            'settings' => $settings,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson('/api/user/settings', [
                'path' => 'invalid.path',
                'value' => 'test',
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test updating settings creates defaults if none exist.
     */
    public function test_update_creates_settings_with_defaults_if_none_exist(): void
    {
        // Sicherstellen, dass keine Settings existieren
        $this->assertDatabaseMissing('user_app_settings', [
            'user_id' => $this->user->id,
        ]);

        // PATCH-Request senden
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson('/api/user/settings', [
                'path' => 'theme.mode',
                'value' => 'dark',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Settings updated successfully',
            ]);

        // Settings wurden erstellt
        $this->assertDatabaseHas('user_app_settings', [
            'user_id' => $this->user->id,
        ]);

        // Settings enthalten die geänderte Einstellung
        $userSettings = UserAppSettings::where('user_id', $this->user->id)->first();
        $this->assertNotNull($userSettings);
        $this->assertEquals('dark', $userSettings->settings['theme']['mode']);

        // Andere Defaults sind vorhanden
        $this->assertEquals('dashboard', $userSettings->settings['navigation']['start_page']);
        $this->assertIsArray($userSettings->settings['dashboard']['widgets']);
    }

    /**
     * Test deleting settings.
     */
    public function test_delete_settings(): void
    {
        $settings = UserAppSettingsService::getDefaultSettings();
        UserAppSettings::create([
            'user_id' => $this->user->id,
            'settings' => $settings,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/user/settings');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Settings deleted successfully. Defaults will be used.',
            ]);

        $this->assertDatabaseMissing('user_app_settings', [
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test validation for invalid theme mode.
     */
    public function test_validation_for_invalid_theme_mode(): void
    {
        $settings = UserAppSettingsService::getDefaultSettings();
        $settings['theme']['mode'] = 'invalid';

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user/settings', [
                'settings' => $settings,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('settings.theme.mode');
    }

    /**
     * Test validation for too many footer items.
     */
    public function test_validation_for_too_many_footer_items(): void
    {
        $settings = UserAppSettingsService::getDefaultSettings();

        // Add a 6th footer item
        $settings['navigation']['footer_items'][] = [
            'id' => 'extra',
            'label' => 'Extra',
            'icon' => 'extra',
            'route' => 'extra',
            'order' => 6,
            'visible' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user/settings', [
                'settings' => $settings,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('settings.navigation.footer_items');
    }

    /**
     * Test validation for invalid footer item id.
     */
    public function test_validation_for_invalid_footer_item_id(): void
    {
        $settings = UserAppSettingsService::getDefaultSettings();
        $settings['navigation']['footer_items'][0]['id'] = 'invalid_id';

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user/settings', [
                'settings' => $settings,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('settings.navigation.footer_items.0.id');
    }

    /**
     * Test authentication is required.
     */
    public function test_authentication_is_required(): void
    {
        $response = $this->getJson('/api/user/settings');
        $response->assertStatus(401);

        $response = $this->postJson('/api/user/settings', [
            'settings' => UserAppSettingsService::getDefaultSettings(),
        ]);
        $response->assertStatus(401);

        $response = $this->patchJson('/api/user/settings', [
            'path' => 'theme.mode',
            'value' => 'dark',
        ]);
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/user/settings');
        $response->assertStatus(401);
    }

    /**
     * Test updating nested path.
     */
    public function test_update_nested_path(): void
    {
        $settings = UserAppSettingsService::getDefaultSettings();
        UserAppSettings::create([
            'user_id' => $this->user->id,
            'settings' => $settings,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson('/api/user/settings', [
                'path' => 'modules.care.default_tab',
                'value' => 'checkin',
            ]);

        $response->assertStatus(200);

        $userSettings = UserAppSettings::where('user_id', $this->user->id)->first();
        $this->assertEquals('checkin', $userSettings->settings['modules']['care']['default_tab']);
    }

    /**
     * Test complete settings overwrite.
     */
    public function test_complete_settings_overwrite(): void
    {
        // Create initial settings
        $initialSettings = UserAppSettingsService::getDefaultSettings();
        UserAppSettings::create([
            'user_id' => $this->user->id,
            'settings' => $initialSettings,
        ]);

        // Modify and save new settings
        $newSettings = UserAppSettingsService::getDefaultSettings();
        $newSettings['theme']['mode'] = 'dark';
        $newSettings['dashboard']['style'] = 'compact';

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user/settings', [
                'settings' => $newSettings,
            ]);

        $response->assertStatus(200);

        $userSettings = UserAppSettings::where('user_id', $this->user->id)->first();
        $this->assertEquals('dark', $userSettings->settings['theme']['mode']);
        $this->assertEquals('compact', $userSettings->settings['dashboard']['style']);
    }
}


<?php

namespace App\Providers;

use App\Services\KeycloakService as KeycloakSocialiteProvider;
use App\Settings\KeyCloakSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class KeycloakProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        Socialite::extend('keycloak', function ($app) {
            $config = $this->getKeycloakConfig();

            Log::info('KeycloakProvider - Building provider with config', [
                'client_id' => $config['client_id'] ?? 'not set',
                'redirect' => $config['redirect'] ?? 'not set',
                'base_url' => $config['base_url'] ?? 'not set',
                'realm' => $config['realm'] ?? 'not set',
                'has_client_secret' => !empty($config['client_secret']),
            ]);

            return Socialite::buildProvider(KeycloakSocialiteProvider::class, $config);
        });
    }

    /**
     * Get Keycloak configuration from Settings or .env as fallback
     *
     * @return array
     */
    protected function getKeycloakConfig(): array
    {
        try {
            $keycloakSettings = app(KeyCloakSetting::class);

            return [
                'client_id' => $this->getConfigValue($keycloakSettings->client_id ?? null, 'KEYCLOAK_CLIENT_ID'),
                'client_secret' => $this->getConfigValue($keycloakSettings->client_secret ?? null, 'KEYCLOAK_CLIENT_SECRET'),
                'redirect' => $this->getConfigValue($keycloakSettings->redirect_uri ?? null, 'KEYCLOAK_REDIRECT_URI'),
                'base_url' => $this->getConfigValue($keycloakSettings->base_url ?? null, 'KEYCLOAK_BASE_URL'),
                'realm' => $this->getConfigValue($keycloakSettings->realm ?? null, 'KEYCLOAK_REALM', 'master'),
            ];
        } catch (\Exception $e) {
            // Fallback to .env if settings are not available at all
            return [
                'client_id' => env('KEYCLOAK_CLIENT_ID'),
                'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
                'redirect' => env('KEYCLOAK_REDIRECT_URI'),
                'base_url' => env('KEYCLOAK_BASE_URL'),
                'realm' => env('KEYCLOAK_REALM', 'master'),
            ];
        }
    }

    /**
     * Get config value from Settings or fallback to .env
     *
     * @param mixed $settingValue
     * @param string $envKey
     * @param mixed $default
     * @return mixed
     */
    protected function getConfigValue($settingValue, string $envKey, $default = null)
    {
        // If setting value exists and is not empty, use it
        if (!empty($settingValue) && $settingValue !== null) {
            return $settingValue;
        }

        // Otherwise fallback to .env
        return env($envKey, $default);
    }
}


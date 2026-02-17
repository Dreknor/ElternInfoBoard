<?php

namespace App\Providers;

use App\Services\KeycloakService as KeycloakSocialiteProvider;
use App\Settings\KeyCloakSetting;
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
            try {
                $keycloakSettings = app(KeyCloakSetting::class);

                $config = [
                    'client_id' => $keycloakSettings->client_id,
                    'client_secret' => $keycloakSettings->client_secret,
                    'redirect' => $keycloakSettings->redirect_uri,
                    'base_url' => $keycloakSettings->base_url,
                    'realm' => $keycloakSettings->realm,
                ];
            } catch (\Exception $e) {
                // Fallback to config if settings are not available
                $config = $app['config']['services.keycloak'];
            }

            return Socialite::buildProvider(KeycloakSocialiteProvider::class, $config);
        });

    }
}


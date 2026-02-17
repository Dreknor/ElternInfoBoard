<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use SocialiteProviders\Keycloak\Provider;

class KeycloakProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->app->make(SocialiteFactory::class)->extend('keycloak', function ($app) {
            $config = $app['config']['services.keycloak'];
            return new Provider(
                $app['request'],
                $config['client_id'],
                $config['client_secret'],
                $config['redirect'],
                $config
            );
        });
    }
}


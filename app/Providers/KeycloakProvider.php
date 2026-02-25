<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Keycloak\Provider as KeycloakSocialiteProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class KeycloakProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->app['events']->listen(SocialiteWasCalled::class, function (SocialiteWasCalled $event) {
            $event->extendSocialite('keycloak', KeycloakSocialiteProvider::class);
        });
    }
}


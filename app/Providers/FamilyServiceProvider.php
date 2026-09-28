<?php

namespace App\Providers;

use App\Model\Child;
use App\Policies\ChildPolicy;
use App\Services\Family\ChildCentricFamilyResolver;
use App\Services\Family\FamilyResolver;
use App\Services\Family\LegacySorg2FamilyResolver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Kind-zentriertes Familienmodell: Resolver-Schalter und Policies.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §5, §11
 */
class FamilyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bewusst kein Singleton: der Schalter wird bei jeder Auflösung gelesen,
        // damit ein Umschalten (Rollback) ohne Neustart wirkt.
        $this->app->bind(FamilyResolver::class, function ($app) {
            return config('family.resolver') === FamilyResolver::MODE_CHILD_CENTRIC
                ? $app->make(ChildCentricFamilyResolver::class)
                : $app->make(LegacySorg2FamilyResolver::class);
        });
    }

    public function boot(): void
    {
        Gate::policy(Child::class, ChildPolicy::class);
    }
}

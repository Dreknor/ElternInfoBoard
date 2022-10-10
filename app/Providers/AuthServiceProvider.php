<?php

namespace App\Providers;

use App\Model\Liste;
use App\Model\Termin;
use App\Policies\TerminListenPolicy;
use App\Policies\TerminPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
        Termin::class => TerminPolicy::class,
        Liste::class => TerminListenPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}

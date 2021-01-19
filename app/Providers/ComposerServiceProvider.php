<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // You can use a class for composer
        // you will need ModulesComposer@compose method
        //
        View::composer(
            'layouts.elements.modules', 'App\Http\View\Composers\ModulesComposer'
        );

        View::composer(
            'include.losung', 'App\Http\View\Composers\LosungComposer'
        );

        View::composer(
            'reinigung.nachricht', 'App\Http\View\Composers\ReinigungComposer'
        );

        View::composer(
            'nachrichten.start', 'App\Http\View\Composers\NachrichtenComposer'
        );
        View::composer(
            'termine.nachricht', 'App\Http\View\Composers\TermineComposer'
        );
    }
}

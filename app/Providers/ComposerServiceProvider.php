<?php

namespace App\Providers;

use App\Http\View\Composers\LosungComposer;
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
            'layouts.elements.modules', \App\Http\View\Composers\ModulesComposer::class
        );

        View::composer(
            'include.losung', LosungComposer::class
        );

        View::composer(
            'reinigung.nachricht', \App\Http\View\Composers\ReinigungComposer::class
        );

        View::composer(
            'nachrichten.start', \App\Http\View\Composers\NachrichtenComposer::class
        );
        View::composer(
            'termine.nachricht', \App\Http\View\Composers\TermineComposer::class
        );
    }
}

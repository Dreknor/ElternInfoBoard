<?php

namespace App\Providers;

use App\Http\View\Composers\CareComposer;
use App\Http\View\Composers\DiseaseComposer;
use App\Http\View\Composers\LoginComposer;
use App\Http\View\Composers\LosungComposer;
use App\Http\View\Composers\ModulesComposer;
use App\Http\View\Composers\NachrichtenComposer;
use App\Http\View\Composers\NotificationComposer;
use App\Http\View\Composers\ReinigungComposer;
use App\Http\View\Composers\TermineComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // You can use a class for composer
        // you will need ModulesComposer@compose method
        //
        View::composer(
            'layouts.elements.modules', ModulesComposer::class
        );

        View::composer(
            'layouts.app',
            NotificationComposer::class
        );

        View::composer(
            'include.losung', LosungComposer::class
        );

        View::composer(
            'reinigung.nachricht', ReinigungComposer::class
        );

        View::composer(
            'nachrichten.start', NachrichtenComposer::class
        );

        View::composer(
            'krankmeldung.diseases', DiseaseComposer::class
        );

        View::composer(
            'termine.nachricht', TermineComposer::class
        );


        View::composer(
            'auth.login', LoginComposer::class
        );

        View::composer(
            'anwesenheit.index', CareComposer::class
        );
    }
}

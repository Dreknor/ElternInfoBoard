<?php

namespace App\Http\View\Composers;

use App\Services\NavigationService;

/**
 * Stellt dem Layout die aktive Menü-Darstellung ($navStyle) und der
 * kompakten Sidebar das gruppierte Menü ($navigation) bereit.
 */
class NavigationComposer
{
    public function __construct(private NavigationService $navigation) {}

    public function compose($view): void
    {
        $user = auth()->user();

        if ($view->name() === 'layouts.app') {
            $view->with('navStyle', $this->navigation->resolveStyle($user));

            return;
        }

        if (! $user) {
            $view->with('navigation', ['top' => [], 'groups' => [], 'admin_top' => [], 'admin' => []]);

            return;
        }

        $notifications = $view->getData()['notifications'] ?? collect();

        $view->with('navigation', $this->navigation->build(
            $user,
            collect(ModulesComposer::activeModules()),
            collect($notifications),
            request()->path(),
        ));
    }
}

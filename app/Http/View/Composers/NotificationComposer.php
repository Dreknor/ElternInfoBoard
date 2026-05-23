<?php

namespace App\Http\View\Composers;

class NotificationComposer
{
    public function compose($view): void
    {
        if (!auth()->check()) {
            $view->with([
                'notifications'  => collect([]),
                'user'           => null,
                'ucsLinkEnabled' => false,
            ]);
            return;
        }

        $notifications = auth()->user()->notifications()->orderBy('important')->get();

        // UCS-Link-Button im Nav anzeigen, wenn UCS aktiviert und Nutzer noch kein ucs_uuid hat
        $ucsLinkEnabled = false;
        try {
            $ucsLinkEnabled = (bool) config('services.keycloak.enabled')
                && app(\App\Settings\UcsSetting::class)->enabled;
        } catch (\Throwable) {
            $ucsLinkEnabled = false;
        }

        $view->with([
            'notifications'  => $notifications,
            'user'           => auth()->user(),
            'ucsLinkEnabled' => $ucsLinkEnabled,
        ]);
    }
}



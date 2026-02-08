<?php

namespace App\Http\View\Composers;

class NotificationComposer
{
    public function compose($view): void
    {
        if (!auth()->check()) {
            $view->with([
                'notifications' => collect([]),
                'user' => null,
            ]);
            return;
        }

        $notifications = auth()->user()->notifications()->orderBy('important')->get();
        $view->with([
            'notifications' => $notifications,
            'user' => auth()->user(),
        ]);
    }
}

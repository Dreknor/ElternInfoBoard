<?php

namespace App\Http\View\Composers;

class NotificationComposer
{
    public function compose($view): void
    {

        $notifications = auth()->user()->notifications()->orderBy('important')->get();
        $view->with([
            'notifications' => $notifications,
            'user' => auth()->user(),
        ]);
    }
}

<?php

namespace App\Http\View\Composers;

use App\Model\Post;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class NotificationComposer
{
    public function compose($view): void
    {


        $notifications = auth()->user()->notifications()->where('read', 0)->orderBy('important')->get();
        $view->with([
            'notifications' => $notifications,
            'user' => auth()->user(),
        ]);
    }
}

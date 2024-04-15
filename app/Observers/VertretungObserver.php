<?php

namespace App\Observers;

use App\Model\Notification;
use App\Model\Vertretung;
use App\Notifications\VertretungsplanNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VertretungObserver
{
    public function created(Vertretung $vertretung)
    {
        $group = $vertretung->group;
        $users = $group->users;

        Log::info('Vertretung created: ' . $vertretung->id . ' for ' . $group->name . ' on ' . $vertretung->date . ' in the ' . $vertretung->stunde . ' hour.');
        Log::info('Sending notification to ' . $users->count() . ' users.');

        $notifications = [];

        foreach ($users as $user) {
            $notifications[]= array(
                'title' => 'Vertretung',
                'url' => '/vertretungsplan/',
                'type' => 'vertretung',
                'message' => 'Änderung im Vertretungsplan für ' . $group->name . ' am ' . Carbon::createFromFormat('Y-m-d', $vertretung->date)->format('d.m.Y') . ' in der ' . $vertretung->stunde . ' Stunde.',
                'user_id' => $user->id,
            );

            if ($user->webPushSubscriptions->count() > 0 and $user->can('testing')) {
                Log::info('Sending notification to ' . $user->name);
                $user->notify(new VertretungsplanNotification($notification->title, $notification->message));
            }
        }

        Notification::insert($notifications);
    }


}

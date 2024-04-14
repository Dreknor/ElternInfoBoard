<?php

namespace App\Observers;

use App\Model\Notification;
use App\Model\Vertretung;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VertretungObserver
{
    public function created(Vertretung $vertretung)
    {
        $group = $vertretung->group;
        $users = $group->users;

        foreach ($users as $user) {
            //$user->notify(new VertretungNotification($vertretung));
            $notification = new Notification([
                'title' => 'Vertretung',
                'url' => '/vertretungsplan/',
                'type' => 'vertretung',
                'message' => 'Änderung im Vertretungsplan für ' . $group->name . ' am ' . Carbon::createFromFormat('Y-m-d', $vertretung->date)->format('d.m.Y') . ' in der ' . $vertretung->stunde . ' Stunde.'

            ]);
            $user->notifications()->save($notification);
        }
    }


}

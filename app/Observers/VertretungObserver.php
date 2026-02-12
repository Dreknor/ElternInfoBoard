<?php

namespace App\Observers;

use App\Model\Notification;
use App\Model\Vertretung;
use Carbon\Carbon;

class VertretungObserver
{
    public function created(Vertretung $vertretung): void
    {
        // Versuche zuerst die Gruppe zu laden (altes System)
        if ($vertretung->klasse) {
            $group = $vertretung->group;
            if ($group) {
                $users = $group->users;
                $groupName = $group->name;

                $notifications = [];

                foreach ($users as $user) {
                    $notifications[] = [
                        'title' => 'Vertretung',
                        'url' => url('/vertretungsplan/'),
                        'type' => 'vertretung',
                        'message' => 'Änderung im Vertretungsplan für '.$groupName.' am '.Carbon::createFromFormat('Y-m-d', $vertretung->date)->format('d.m.Y').' in der '.$vertretung->stunde.' Stunde.',
                        'user_id' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($notifications)) {
                    Notification::insert($notifications);
                }
            }
        }
        // Falls klasse_kurzform gesetzt ist (neues System)
        elseif ($vertretung->klasse_kurzform) {
            $klasse = $vertretung->klasse;
            if ($klasse) {
                // TODO: Hier müssen die Benutzer ermittelt werden, die zur Stundenplan-Klasse gehören
                // Dies hängt davon ab, wie die Beziehung zwischen Benutzern und Stundenplan-Klassen implementiert ist
            }
        }
    }
}

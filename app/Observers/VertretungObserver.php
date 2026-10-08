<?php

namespace App\Observers;

use App\Model\Group;
use App\Model\Vertretung;
use App\Traits\NotificationTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class VertretungObserver
{
    use NotificationTrait;

    /**
     * Mitglieder der Klasse benachrichtigen. Das MitarbeiterBoard überträgt oft viele Einträge
     * nacheinander: Eine ungelesene Vertretungs-Benachrichtigung wird deshalb aktualisiert
     * statt vervielfacht, Push höchstens alle 10 Minuten (siehe NotificationTrait).
     */
    public function created(Vertretung $vertretung): void
    {
        if ($vertretung->klasse) {
            // Altes System: Vertretung verweist auf die Gruppe
            $groups = $vertretung->group ? new Collection([$vertretung->group]) : new Collection;
        } elseif ($vertretung->klasse_kurzform) {
            // Neues System: Kurzform der Stundenplan-Klasse = Name der Klassengruppe
            $groups = Group::withoutGlobalScopes()->where('name', $vertretung->klasse_kurzform)->get();
        } else {
            return;
        }

        foreach ($groups as $group) {
            $this->notify(
                $group->users()->get()->unique('id'),
                'Vertretung',
                'Änderung im Vertretungsplan für '.$group->name.' am '.Carbon::parse($vertretung->date)->format('d.m.Y')
                    .' in der '.$vertretung->stunde.'. Stunde.',
                false,
                url('vertretungsplan'),
                'vertretung',
                '',
                true,
            );
        }
    }
}

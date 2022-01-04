<?php

namespace App\Http\Controllers;

use App\Model\Termin;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ICalController extends Controller
{

    public function createICal($uuid)
    {
        $user = User::where('uuid', $uuid)->firstOrFail();
        $Termine = $user->termine;

        //Termine aus Listen holen
        $listen_termine = $user->listen_eintragungen()->whereDate('termin', '>', Carbon::now()->startOfDay())->get();

        //Ergänze Listeneintragungen
        if (!is_null($listen_termine) and count($listen_termine) > 0) {
            foreach ($listen_termine as $termin) {
                $newTermin = new Termin([
                    'terminname' => $termin->liste->listenname,
                    'start' => $termin->termin,
                    'ende' => $termin->termin->copy()->addMinutes($termin->liste->duration),
                    'fullDay' => null,
                ]);
                $Termine->push($newTermin);
            }
        }

        //Listentermine von Sorg2
        if (!is_null($user->sorgeberechtigter2)) {
            foreach ($user->sorgeberechtigter2->listen_eintragungen()->whereDate('termin', '>', Carbon::now()->startOfDay())->get() as $termin) {
                $newTermin = new Termin([
                    'terminname' => $termin->liste->listenname,
                    'start' => $termin->termin,
                    'ende' => $termin->termin->copy()->addMinutes($termin->liste->duration),
                    'fullDay' => null,
                ]);
                $Termine->push($newTermin);
            }
        }

        $Termine = $Termine->unique('id');
        $Termine = $Termine->sortBy('start');

        //ICAL erstellen

        define('ICAL_FORMAT', 'Ymd\THis\Z');

        $icalObject = "BEGIN:VCALENDAR
           VERSION:2.0
           METHOD:PUBLISH
           PRODID:-//" . config('app.name') . "//Termine//DE\n
           ";

        // loop over events
        foreach ($Termine as $event) {
            $icalObject .=
                "BEGIN:VEVENT
               DTSTART:" . $event->start->format('Ymd\THis') . "
               DTEND:" . $event->ende->format('Ymd\THis') . "
               DTSTAMP:" . date(ICAL_FORMAT, strtotime($event->created_at ? $event->created_at : Carbon::now())) . "
               UID:$event->id,
               SUMMARY: $event->terminname
               END:VEVENT\n";
        }

        // close calendar
        $icalObject .= "END:VCALENDAR";

        // Set the headers
        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="cal.ics"');

        $icalObject = str_replace(' ', '', $icalObject);

        return $icalObject;
    }
}

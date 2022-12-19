<?php

namespace App\Http\Controllers;

use App\Model\Termin;
use App\Model\User;
use Carbon\Carbon;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

class ICalController extends Controller
{
    /**
     * @param $uuid
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|void
     */
    public function createICal($uuid)
    {
        $user = User::where('uuid', $uuid)->firstOrFail();
        if ($user->releaseCalendar == true) {
            $Termine = $user->termine;

            //Termine aus Listen holen
            $listen_termine = $user->listen_eintragungen()->whereDate('termin', '>', Carbon::now()->startOfDay())->get();

            //Ergänze Listeneintragungen
            if (! is_null($listen_termine) and count($listen_termine) > 0) {
                foreach ($listen_termine as $termin) {
                    $newTermin = new Termin([
                        'terminname' => $termin->liste->listenname,
                        'start' => $termin->termin->timezone('Europe/Berlin'),
                        'ende' => $termin->termin->copy()->addMinutes($termin->liste->duration),
                        'fullDay' => null,
                    ]);
                    $Termine->push($newTermin);
                }
            }

            //Listentermine von Sorg2
            if (! is_null($user->sorgeberechtigter2)) {
                foreach ($user->sorgeberechtigter2->listen_eintragungen()->whereDate('termin', '>', Carbon::now()->startOfDay())->get() as $termin) {
                    $newTermin = new Termin([
                        'terminname' => $termin->liste->listenname,
                        'start' => $termin->termin->timezone('Europe/Berlin'),
                        'ende' => $termin->termin->copy()->addMinutes($termin->liste->duration),
                        'fullDay' => null,
                    ]);
                    $Termine->push($newTermin);
                }
            }

            $Termine = $Termine->unique('id');
            $Termine = $Termine->sortBy('start');

            $icalObject = Calendar::create(config('app.name'));

            // loop over events
            foreach ($Termine as $event) {
                if ($event->fullDay == true) {
                    $icalObject->event(Event::create()
                        ->name($event->terminname)
                        ->uniqueIdentifier(($event->id) ? $event->id : uuid_create())
                        ->startsAt($event->start->timezone('Europe/Berlin'))
                        ->withoutTimezone()
                        ->fullDay());
                } else {
                    $icalObject->event(Event::create()
                        ->name($event->terminname)
                        ->startsAt($event->start->timezone('Europe/Berlin'))
                        ->endsAt($event->ende->timezone('Europe/Berlin'))
                        ->uniqueIdentifier(($event->id) ? $event->id : uuid_create())
                    );
                }
            }

            return response($icalObject->get(), 200, [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.config('app.name').'.ics"',
            ]);
        } else {
            abort(404);
        }
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function publicICal()
    {
        $Termine = Termin::where('public', true)->get();
        $Termine = $Termine->unique('id');
        $Termine = $Termine->sortBy('start');

        //ICAL erstellen

        $icalObject = Calendar::create(config('app.name'));

        // loop over events
        foreach ($Termine as $event) {
            if ($event->fullDay == true) {
                $icalObject->event(Event::create()
                    ->name($event->terminname)
                    ->uniqueIdentifier(($event->id) ? $event->id : uuid_create())
                    ->startsAt($event->start->timezone('Europe/Berlin'))
                    ->fullDay());
            } else {
                $icalObject->event(Event::create()
                    ->name($event->terminname)
                    ->startsAt($event->start->timezone('Europe/Berlin'))
                    ->endsAt($event->ende->timezone('Europe/Berlin'))
                    ->uniqueIdentifier(($event->id) ? $event->id : uuid_create())
                );
            }
        }

        return response($icalObject->get(), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.config('app.name').'.ics"',
        ]);
    }
}

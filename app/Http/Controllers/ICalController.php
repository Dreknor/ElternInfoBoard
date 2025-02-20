<?php

namespace App\Http\Controllers;

use App\Model\Termin;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

class ICalController extends Controller
{
    /**
     * @param $uuid
     * @return Response
     */
    public function createICal($uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->first();

            if (is_null($user)) {
                return response('Kalender nicht gefunden', 404);
            }

            $prefix = ($user->calendar_prefix != null) ? '(' . $user->calendar_prefix . ') ' : '';

            if ($user->releaseCalendar) {
                $Termine = $user->termine;

                //Termine aus Listen holen
                $listen_termine = $user->listen_termine()->whereDate('termin', '>', Carbon::now()->startOfDay())->get();

                //Ergänze Listeneintragungen
                if (! is_null($listen_termine) and count($listen_termine) > 0) {
                    foreach ($listen_termine as $termin) {
                        $newTermin = new Termin([
                            'terminname' => $prefix . '' . $termin->liste->listenname,
                            'start' => $termin->termin->timezone('Europe/Berlin'),
                            'ende' => $termin->termin->copy()->addMinutes($termin->liste->duration),
                            'fullDay' => null,
                        ]);
                        $Termine->push($newTermin);
                    }
                }

                //Listentermine von Sorg2
                if (! is_null($user->sorgeberechtigter2)) {
                    foreach ($user->sorgeberechtigter2->listen_termine()->whereDate('termin', '>', Carbon::now()->startOfDay())->get() as $termin) {
                        $newTermin = new Termin([
                            'terminname' => $prefix . '' . $termin->liste->listenname,
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
                    if ($event->fullDay) {
                        $icalObject->event(Event::create()
                            ->name($prefix . '' . $event->terminname)
                            ->uniqueIdentifier(($event->id) ?: uuid_create())
                            ->startsAt($event->start->timezone('Europe/Berlin'))
                            ->endsAt($event->ende->timezone('Europe/Berlin'))
                            ->withoutTimezone()
                            ->fullDay());
                    } else {
                        $icalObject->event(Event::create()
                            ->name($prefix . '' . $event->terminname)
                            ->startsAt($event->start->timezone('Europe/Berlin'))
                            ->endsAt($event->ende->timezone('Europe/Berlin'))
                            ->uniqueIdentifier(($event->id) ?: uuid_create())
                        );
                    }
                }

                return response($icalObject->get(), 200, [
                    'Content-Type' => 'text/calendar; charset=utf-8',
                    'Content-Disposition' => 'attachment; filename="'.config('app.name').'.ics"',
                ]);
            } else {
                return response('Kalender nicht freigegeben', 403);
            }
        } catch (\Exception $e) {
            return response('Kalender nicht gefunden', 404);
        }

    }

    /**
     * @return Response
     */
    public function publicICal()
    {
        $Termine = Termin::where('public', 1)->get();
        $Termine = $Termine->unique('id');
        $Termine = $Termine->sortBy('start');

        //ICAL erstellen

        $icalObject = Calendar::create(config('app.name'));

        // loop over events
        foreach ($Termine as $event) {
            if ($event->fullDay) {
                $icalObject->event(Event::create()
                    ->name($event->terminname)
                    ->uniqueIdentifier(($event->id) ?: uuid_create())
                    ->startsAt($event->start->timezone('Europe/Berlin'))
                    ->endsAt($event->ende->timezone('Europe/Berlin'))
                    ->fullDay());
            } else {
                $icalObject->event(Event::create()
                    ->name($event->terminname)
                    ->startsAt($event->start->timezone('Europe/Berlin'))
                    ->endsAt($event->ende->timezone('Europe/Berlin'))
                    ->uniqueIdentifier(($event->id) ?: uuid_create())
                );
            }
        }

        return response($icalObject->get(), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.config('app.name').'.ics"',
        ]);
    }
}

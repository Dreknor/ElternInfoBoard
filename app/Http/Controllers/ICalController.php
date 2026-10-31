<?php

namespace App\Http\Controllers;

use App\Model\listen_termine;
use App\Model\Termin;
use App\Model\User;
use App\Settings\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

class ICalController extends Controller
{
    /**
     * @return Response
     */
    public function createICal($uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->first();

            if (is_null($user)) {
                return response('Kalender nicht gefunden', 404);
            }

            $prefix = ($user->calendar_prefix != null) ? '('.$user->calendar_prefix.') ' : '';

            if ($user->releaseCalendar) {
                $Termine = $user->visibleTermine()->get();

                // Termine aus Listen holen
                // Listentermine der ganzen Familie (FamilyResolver)
                $listen_termine = listen_termine::query()
                    ->whereIn('reserviert_fuer', $user->familyUserIds())
                    ->whereDate('termin', '>', Carbon::now()->startOfDay())
                    ->with('liste')
                    ->get();

                // Ergänze Listeneintragungen
                if (! is_null($listen_termine) and count($listen_termine) > 0) {
                    foreach ($listen_termine as $termin) {
                        $newTermin = new Termin([
                            'terminname' => $prefix.''.$termin->liste->listenname,
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
                            ->name($prefix.''.$event->terminname)
                            ->uniqueIdentifier(($event->id) ?: uuid_create())
                            ->startsAt($event->start->timezone('Europe/Berlin'))
                            ->endsAt($event->ende->timezone('Europe/Berlin'))
                            ->withoutTimezone()
                            ->fullDay());
                    } else {
                        $icalObject->event(Event::create()
                            ->name($prefix.''.$event->terminname)
                            ->startsAt($event->start->timezone('Europe/Berlin'))
                            ->endsAt($event->ende->timezone('Europe/Berlin'))
                            ->uniqueIdentifier(($event->id) ?: uuid_create())
                        );
                    }
                }

                // Vorschlag 7: eigene Reinigungsdienste in den persönlichen Kalender aufnehmen
                // inkl. der Dienste anderer Familienmitglieder (FamilyResolver)
                // (nur bei aktivem Modul "Reinigung")
                $reinigungTermine = ! \App\Services\App\Modules::isActiveFor($user, 'Reinigung') ? collect() : \App\Model\Reinigung::query()
                    ->whereIn('users_id', $user->familyUserIds())
                    ->whereDate('datum', '>', Carbon::now()->subWeek()->startOfDay())
                    ->get()
                    ->unique('id');

                foreach ($reinigungTermine as $reinigung) {
                    $wocheStart = $reinigung->datum->copy()->startOfWeek();
                    $wocheEnde = $reinigung->datum->copy()->endOfWeek();

                    $icalObject->event(Event::create()
                        ->name($prefix.'Reinigungsdienst'.($reinigung->aufgabe ? ': '.$reinigung->aufgabe : ''))
                        ->uniqueIdentifier('reinigung-'.$reinigung->id)
                        ->startsAt($wocheStart)
                        ->endsAt($wocheEnde)
                        ->withoutTimezone()
                        ->fullDay());
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
     * Öffentliche Termine als HTML-Seite (ohne Anmeldung) inkl. schema.org-Event-Daten (JSON-LD).
     */
    public function publicHtml(): View
    {
        $settings = app(GeneralSetting::class);
        abort_unless($settings->public_termine_enabled, 404);

        $termine = Termin::query()
            ->where('public', 1)
            ->where('ende', '>=', Carbon::today())
            ->orderBy('start')
            ->get();

        $pageUrl = route('termine.public');

        return view('termine.public', [
            'termineNachMonat' => $termine->groupBy(
                fn (Termin $termin) => $termin->start->timezone('Europe/Berlin')->locale('de')->isoFormat('MMMM YYYY')
            ),
            'icsUrl' => url('ical/publicEvents'),
            'pageUrl' => $pageUrl,
            'jsonLd' => $this->publicJsonLd($termine, $settings, $pageUrl),
        ]);
    }

    /**
     * Strukturierte Daten nach schema.org/Event, damit Suchmaschinen (Google) die Termine
     * als Veranstaltungen erkennen. Google verlangt für Rich Results einen Ort mit Adresse –
     * dieser kommt aus den allgemeinen Einstellungen.
     */
    private function publicJsonLd(Collection $termine, GeneralSetting $settings, string $pageUrl): array
    {
        $appName = $settings->app_name ?: config('app.name');

        $organizer = [
            '@type' => 'Organization',
            'name' => $appName,
            'url' => url('/'),
        ];

        $location = [
            '@type' => 'Place',
            'name' => $settings->public_termine_location_name ?: $appName,
        ];
        $address = array_filter([
            'streetAddress' => $settings->public_termine_location_street,
            'postalCode' => $settings->public_termine_location_postal_code,
            'addressLocality' => $settings->public_termine_location_city,
        ]);
        if ($address !== []) {
            $location['address'] = ['@type' => 'PostalAddress'] + $address + ['addressCountry' => 'DE'];
        }

        $logo = ($settings->logo ?? 'logo.png') === 'logo.png'
            ? asset('img/logo.png')
            : url('storage/img/'.$settings->logo);

        $events = $termine->map(function (Termin $termin) use ($organizer, $location, $logo, $pageUrl) {
            $start = $termin->start->timezone('Europe/Berlin');
            $ende = $termin->ende->timezone('Europe/Berlin');

            return [
                '@type' => 'Event',
                '@id' => $pageUrl.'#termin-'.$termin->id,
                'name' => $termin->terminname,
                'url' => $pageUrl.'#termin-'.$termin->id,
                // Ganztägige Termine nur mit Datum, sonst ISO 8601 mit Zeitzonen-Offset
                'startDate' => $termin->fullDay ? $start->toDateString() : $start->toIso8601String(),
                'endDate' => $termin->fullDay ? $ende->toDateString() : $ende->toIso8601String(),
                'eventStatus' => 'https://schema.org/EventScheduled',
                'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                'location' => $location,
                'organizer' => $organizer,
                'image' => [$logo],
            ];
        })->values()->all();

        return [
            '@context' => 'https://schema.org',
            '@graph' => $events,
        ];
    }

    /**
     * @return Response
     */
    public function publicICal()
    {
        abort_unless(app(GeneralSetting::class)->public_termine_enabled, 404);

        $Termine = Termin::where('public', 1)->get();
        $Termine = $Termine->unique('id');
        $Termine = $Termine->sortBy('start');

        // ICAL erstellen

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

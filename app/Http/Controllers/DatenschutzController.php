<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PDF;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatenschutzController extends Controller
{


    /**
     * @param Request $request
     * @return Application|Factory|View
     */
    public function show(Request $request): Application|Factory|View
    {
        $user = $request->user();

        return view('datenschutz.show', [
            'user' => $user,
        ]);
    }

    /**
     * Exportiert alle gespeicherten Nutzerdaten als JSON (Art. 20 DSGVO)
     */
    public function exportJson(Request $request): StreamedResponse
    {
        $user = $request->user();

        $data = $this->collectUserData($user);

        $filename = Carbon::now()->format('Y-m-d') . '_datenschutz_' . str($user->name)->slug() . '.json';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(
            fn () => print($json),
            $filename,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Exportiert alle gespeicherten Nutzerdaten als PDF (Art. 15 DSGVO)
     */
    public function exportPdf(Request $request): Response
    {
        $user = $request->user();

        $pdf = PDF::loadView('pdf.datenschutz', [
            'user'     => $user,
            'exportAt' => Carbon::now(),
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = Carbon::now()->format('Y-m-d') . '_datenschutz_' . str($user->name)->slug() . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Sammelt alle relevanten Nutzerdaten für den Export.
     */
    private function collectUserData($user): array
    {
        $listenTermine = $user->getListenTermine();
        $schickzeiten  = $user->schickzeiten()->withTrashed()->get();
        $pflichtstunden = $user->pflichtstunden()->withTrashed()->get();

        return [
            'exportiert_am'   => Carbon::now()->toIso8601String(),
            'exportiert_fuer' => $user->name,
            'anwendung'       => config('app.name'),
            'hinweis'         => 'Diese Datei enthält alle in der Anwendung gespeicherten personenbezogenen Daten gemäß Art. 15 / Art. 20 DSGVO.',

            'benutzerkonto' => [
                'id'                => $user->id,
                'uuid'              => $user->uuid,
                'name'              => $user->name,
                'email'             => $user->email,
                'oeffentliche_email' => $user->publicMail,
                'oeffentliche_telefon' => $user->publicPhone,
                'konto_aktiv'       => $user->is_active,
                'deaktiviert_am'    => $user->deactivated_at?->toIso8601String(),
                'kennwort'          => '[bcrypt-Hash – nicht entschlüsselbar]',
                'angemeldet_bleiben_cookie' => $user->remember_token ? 'Token vorhanden (verschlüsselt)' : 'Kein Token gespeichert',
                'login_tracking'    => $user->track_login,
                'letzter_login'     => $user->track_login ? $user->last_online_at?->toIso8601String() : 'Tracking deaktiviert',
                'konto_erstellt'    => $user->created_at->toIso8601String(),
                'konto_geaendert'   => $user->updated_at->toIso8601String(),
                'benachrichtigung'  => $user->benachrichtigung,
                'kopie_eigener_mails' => (bool) $user->sendCopy,
                'letzte_info_email' => $user->lastEmail?->toIso8601String(),
                'kalender_freigabe' => (bool) $user->releaseCalendar,
                'kalender_prefix'   => $user->calendar_prefix,
                'sorgeberechtigter_2' => $user->sorgeberechtigter2?->name,
            ],

            'gruppen' => $user->groups()->withoutGlobalScopes()->get()
                ->map(fn ($g) => ['id' => $g->id, 'name' => $g->name])
                ->values()->toArray(),

            'rollen' => $user->roles
                ->map(fn ($r) => $r->name)
                ->values()->toArray(),

            'direkte_berechtigungen' => $user->getDirectPermissions()
                ->map(fn ($p) => $p->name)
                ->values()->toArray(),

            'kinder' => $user->children_rel
                ->map(fn ($c) => [
                    'vorname'  => $c->first_name,
                    'nachname' => $c->last_name,
                    'gruppe'   => $c->group?->name,
                ])
                ->values()->toArray(),

            'api_tokens' => $user->tokens
                ->map(fn ($t) => [
                    'name'            => $t->name,
                    'erstellt_am'     => $t->created_at->toIso8601String(),
                    'zuletzt_genutzt' => $t->last_used_at?->toIso8601String(),
                ])
                ->values()->toArray(),

            'push_registrierungen' => [
                'anzahl'  => $user->pushSubscriptions->count(),
                'hinweis' => 'Endpunkte enthalten keine persönlich zuordenbaren Geräteinformationen.',
            ],

            'krankmeldungen' => $user->krankmeldungen
                ->map(fn ($k) => [
                    'kind'        => $k->name,
                    'von'         => $k->start->toDateString(),
                    'bis'         => $k->ende->toDateString(),
                    'kommentar'   => strip_tags($k->kommentar),
                    'eingereicht' => $k->created_at->toIso8601String(),
                ])
                ->values()->toArray(),

            'listeneintragungen' => ($listenTermine ?? collect())
                ->map(fn ($e) => [
                    'liste'        => $e->liste->listenname,
                    'termin'       => $e->termin?->toIso8601String(),
                    'anmerkung'    => $e->comment,
                    'reserviert_am' => $e->created_at?->toIso8601String(),
                    'geaendert_am' => $e->updated_at?->toIso8601String(),
                ])
                ->values()->toArray(),

            'schickzeiten' => $schickzeiten
                ->map(fn ($s) => [
                    'kind'       => $s->child_name,
                    'wochentag'  => $s->weekday,
                    'art'        => $s->type,
                    'uhrzeit'    => $s->time?->format('H:i'),
                    'erstellt'   => $s->created_at?->toIso8601String(),
                    'geloescht'  => $s->deleted_at?->toIso8601String(),
                ])
                ->values()->toArray(),

            'reinigungstermine' => $user->Reinigung
                ->map(fn ($r) => [
                    'datum'   => $r->datum?->toDateString(),
                    'bereich' => $r->bereich,
                    'aufgabe' => $r->aufgabe,
                ])
                ->values()->toArray(),

            'pflichtstunden' => $pflichtstunden
                ->map(fn ($p) => [
                    'beschreibung' => $p->description,
                    'von'          => $p->start?->toIso8601String(),
                    'bis'          => $p->end?->toIso8601String(),
                    'status'       => $p->approved ? 'bestätigt' : ($p->rejected ? 'abgelehnt' : 'ausstehend'),
                ])
                ->values()->toArray(),

            'rueckmeldungen' => $user->userRueckmeldung
                ->map(fn ($r) => [
                    'nachricht'   => $r->nachricht->header,
                    'rueckmeldung' => strip_tags($r->text),
                    'erstellt'    => $r->created_at?->toIso8601String(),
                    'geaendert'   => $r->updated_at?->toIso8601String(),
                ])
                ->values()->toArray(),

            'eigene_beitraege' => $user->own_posts
                ->map(fn ($p) => [
                    'ueberschrift' => $p->header,
                    'erstellt'     => $p->created_at?->toIso8601String(),
                ])
                ->values()->toArray(),

            'kommentare' => $user->comments
                ->map(fn ($c) => [
                    'beitrag'  => $c->commentable?->header,
                    'kommentar' => $c->body,
                    'erstellt' => $c->created_at?->toIso8601String(),
                ])
                ->values()->toArray(),

            'diskussionen' => $user->discussions
                ->map(fn ($d) => [
                    'ueberschrift' => $d->header,
                    'beitrag'      => strip_tags($d->text),
                    'erstellt'     => $d->created_at->toIso8601String(),
                ])
                ->values()->toArray(),

            'lesebestaedigungen' => $user->read_receipts
                ->map(fn ($r) => [
                    'beitrag'   => $r->post?->header,
                    'bestaetigt' => $r->created_at->toIso8601String(),
                ])
                ->values()->toArray(),

            'abstimmungen' => $user->pollVotes()->with(['poll.post'])->get()
                ->map(fn ($v) => [
                    'nachricht'      => $v->poll?->post?->header,
                    'abfrage_titel'  => $v->poll?->poll_name,
                    'teilgenommen'   => $v->created_at->toIso8601String(),
                    'antwort'        => '[anonym gespeichert – keine Benutzerreferenz in der Datenbank]',
                ])
                ->values()->toArray(),
        ];
    }
}

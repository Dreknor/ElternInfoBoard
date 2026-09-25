<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Model\listen_termine;
use App\Model\User;
use App\Services\App\ListenService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Log;

class ListenController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth:sanctum',
        ];
    }

    /**
     * bestehende Eintragung reservieren
     *
     * Reserviert eine bestehende Eintragung in einer Liste für den User.
     *
     *
     * @group Listen
     *
     * @urlParam eintrag required ID des Eintrags
     *
     * @param  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reserveEintrag(Request $request, Listen_Eintragungen $eintrag)
    {
        try {
            app(ListenService::class)->reserveEintrag($request->user(), $eintrag);
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json(['message' => 'Eintrag reserved'], 200);
    }

    /**
     * Eintrag entfernen
     *
     * Entfernt einen Eintrag aus einer Liste.
     *  * Wenn der Eintrag von dem User erstellt wurde, wird der Eintrag gelöscht.
     *  * Wenn der Eintrag von einem anderen User erstellt wurde, wird der Eintrag freigegeben.
     *
     *  @group Listen
     *
     * @urlParam eintrag required ID des Eintrags
     *
     * @param  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeEintrag(Request $request, Listen_Eintragungen $eintrag)
    {
        try {
            app(ListenService::class)->cancelEintrag($request->user(), $eintrag);
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json(['message' => 'Eintrag removed'], 200);
    }

    /**
     * Listeneintrag hinzufügen
     *
     * Fügt einen Eintrag zu einer Liste hinzu.
     * Es wird geprüft, ob der User bereits einen Eintrag in der Liste hat.
     * Wenn die Liste nur einen Eintrag pro User zulässt, wird geprüft, ob der User bereits einen Eintrag hat.
     *
     *
     * @group Listen
     *
     * @bodyParam eintragung string required
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function addEintrag(Request $request, $liste)
    {
        $request->validate([
            'eintragung' => 'required|string|max:500',
        ]);

        try {
            app(ListenService::class)->addEintrag($request->user(), Liste::findOrFail($liste), $request->eintragung);
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json(['message' => 'Eintrag added'], 200);
    }

    /**
     * index
     *
     * Get all listen for the user
     *
     * @group Listen
     *
     * @responseField listen array Liste
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {

        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->hasPermissionTo('edit terminliste', 'web')) {

            $listen = Liste::query()
                ->whereDate('ende', '>=', now())
                ->get([
                    'id',
                    'listenname',
                    'type',
                    'comment',
                    'besitzer',
                    'visible_for_all',
                    'active',
                    'ende',
                    'duration',
                    'multiple',
                    'make_new_entry',
                    'creates_pflichtstunden',
                ]);
        } else {
            $listen = $user->listen()
                ->whereDate('ende', '>=', now())
                ->where('listen.active', 1)
                ->get([
                    'id',
                    'listenname',
                    'type',
                    'multiple',
                    'ende',
                ]);
        }

        $listen = $listen->unique('id');
        $listen = $listen->sortBy('listenname');

        $result = [];
        foreach ($listen as $key => $liste) {
            // Stelle sicher, dass keine Relationen geladen wurden
            $liste->unsetRelation('users');
            $liste->unsetRelation('groups');
            $liste->unsetRelation('ersteller');
            $liste->unsetRelation('eintragungen');
            $liste->unsetRelation('termine');

            $result[] = $liste;

        }

        return response()->json([
            'listen' => $result], 200);
    }

    /**
     * Get the entries of a list.
     *
     * Retrieves the entries of a list.
     * If the user has permission to edit the list, the names of the users who made or reserved the entries are displayed.
     * Otherwise, it only shows whether the entry is taken or not.
     *
     * @param  User  $user
     * @param  Liste  $liste
     * @return mixed
     */
    private function getEintrag($user, $liste)
    {
        $eintragungen = Listen_Eintragungen::query()
            ->where('listen_id', $liste->id)
            ->get();

        foreach ($eintragungen as $key => $eintragung) {
            if ($eintragung->user_id != null) {

                if ($eintragung->user_id == $user->id) {
                    $eintragungen[$key]->user_id = 'own';
                } else {
                    if ($liste->visible_for_all == true or $user->hasPermissionTo('edit terminliste', 'web')) {
                        // Nur den Namen des Users zurückgeben, nicht das komplette User-Objekt
                        $userName = $eintragung->user ? $eintragung->user->name : 'unbekannt';
                        $eintragungen[$key]->user_id = $userName;
                    } else {
                        $eintragungen[$key]->user_id = 'vergeben';
                    }
                }
            }

            if ($eintragung->created_by == $user->id) {
                $eintragungen[$key]->created_by = 'own';
            } else {
                $eintragungen[$key]->created_by = 'not own';
            }

            // Entferne alle geladenen Relationen, um zu verhindern, dass User-Objekte zurückgegeben werden
            $eintragungen[$key]->unsetRelation('user');
            $eintragungen[$key]->unsetRelation('createdBy');
        }

        return $eintragungen;
    }

    /**
     * Get the appointments of a list.
     *
     * Retrieves the appointments of a list.
     * If the user has permission to edit the list, the names of the users who made or reserved the appointments are displayed.
     * Otherwise, it only shows whether the appointment is taken or not.
     *
     * @param  User  $user
     * @param  Liste  $liste
     * @return mixed
     */
    private function getTermine($user, $liste)
    {

        if ($user->hasPermissionTo('edit terminliste', 'web') or $liste->besitzer == $user->id or $liste->visible_for_all) {
            $termine = listen_termine::query()
                ->where('listen_id', $liste->id)
                ->whereDate('termin', '>=', now())
                ->with('eingetragenePerson')
                ->get();

        } else {

            $termine = listen_termine::query()
                ->where('listen_id', $liste->id)
                ->whereDate('termin', '>=', now())
                ->where(function ($query) use ($user) {
                    if ($user->sorg2 != null) {
                        $query->where('reserviert_fuer', $user->id)
                            ->orWhere('reserviert_fuer', $user->sorg2)
                            ->orWhere('reserviert_fuer', null);
                    } else {
                        $query->where('reserviert_fuer', $user->id)
                            ->orWhere('reserviert_fuer', null);
                    }
                })
                ->with('eingetragenePerson')
                ->get();

        }

        foreach ($termine as $key => $termin) {

            if ($termin->reserviert_fuer != null) {
                if ($termin->reserviert_fuer == $user->id or $termin->reserviert_fuer == $user->sorg2) {
                    $termine[$key]->reserviert_fuer = 'own';
                } else {
                    if ($liste->visible_for_all == true or $user->hasPermissionTo('edit terminliste', 'web')) {
                        // Nur den Namen des Users zurückgeben, nicht das komplette User-Objekt
                        $userName = $termin->eingetragenePerson ? $termin->eingetragenePerson->name : 'unbekannt';
                        $termine[$key]->reserviert_fuer = $userName;
                    } else {
                        $termine[$key]->reserviert_fuer = 'vergeben';
                    }
                }
            }

            // Entferne die eingetragenePerson-Relation, um zu verhindern, dass User-Objekte zurückgegeben werden
            $termine[$key]->unsetRelation('eingetragenePerson');
        }

        return $termine;
    }

    /**
     * Termine oder Eintragungen einer Liste anzeigen
     *
     * Liefert die Termine oder Eintragungen einer Liste.
     * Wenn der User die Berechtigung hat, die Liste zu bearbeiten, werden die Namen der User angezeigt die die Eintragungen gemacht oder reserviert haben.
     * Ansonsten wird nur angezeigt, ob die Eintragung vergeben ist oder nicht.
     *
     * @group Listen
     *
     * @urlParam id required ID der Liste
     *
     * @responseField termine array Termine
     * @responseField eintragungen array Eintragungen
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $liste = Liste::findOrFail($id);

        if ($liste->active == 0 or $liste->ende < now()) {
            return response()->json(['message' => 'Not allowed'], 403);
        }

        // EXISTS-Abfrage statt alle Gruppenmitglieder zu laden (B-31)
        if (! app(ListenService::class)->canAccess($user, $liste)) {
            return response()->json(['message' => 'Not allowed'], 403);
        }

        if ($liste->type == 'termin') {
            $termine = $this->getTermine($user, $liste);
        } else {
            $termine = $this->getEintrag($user, $liste);
        }

        if ($liste->type == 'termin') {
            $key = 'termine';
        } else {
            $key = 'eintragungen';
        }

        return response()->json([
            $key => $termine], 200);
    }

    /**
     * Termin absagen
     *
     * Sagt einen Termin in einer Liste ab.
     *
     * @group Listen
     *
     * @urlParam id required ID des Termins
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelTermin(Request $request, $id)
    {
        try {
            app(ListenService::class)->cancelTermin($request->user(), listen_termine::findOrFail($id), $request->input('text'));
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json(['message' => 'Termin canceled'], 200);
    }

    /**
     *  Termin reservieren
     *
     * Reserviert einen Termin in einer Liste für den User.
     * Wenn die Liste nur eine Reservierung pro User zulässt, wird geprüft, ob der User bereits einen Termin reserviert hat.
     *
     * @group Listen
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reserveTermin(Request $request, $id)
    {
        try {
            app(ListenService::class)->reserveTermin($request->user(), listen_termine::findOrFail($id));
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json(['message' => 'Termin reserved'], 200);
    }
}

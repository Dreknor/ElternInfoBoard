<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Model\listen_termine;
use App\Model\User;
use Illuminate\Http\Request;

/**
 *
 */
class ListenController extends Controller
{

    /**
     *
     */
    public function __construct()
 {
       $this->middleware('auth:sanctum');
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
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reserveEintrag(Request $request, Listen_Eintragungen $eintrag)
 {
     $user = $request->user();

     if (!$user) {
         return response()->json(['message' => 'User not found'], 404);
     }

     $liste = Liste::findOrFail($eintrag->listen_id);

        if ($liste->type != 'eintrag' or $liste->active == 0 or $liste->ende < now() or $eintrag->user_id != null) {
            return response()->json(['message' => 'Not allowed'], 403);
        }

        if ($liste->multiple == false) {
            $eintragungen = Listen_Eintragungen::query()
                ->where('listen_id', $liste->id)
                ->where('user_id', $user->id)
                ->count();

            if ($eintragungen > 0) {
                return response()->json(['message' => 'Not allowed'], 403);
            }
        }


                $eintrag->user_id = $user->id;
                $eintrag->save();


            return response()->json([
                'message' => 'Eintrag reserved'], 200);
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
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeEintrag(Request $request, Listen_Eintragungen $eintrag)
 {
     $user = $request->user();

     if (!$user) {
         return response()->json(['message' => 'User not found'], 404);
     }

     if ($eintrag->user_id == $user->id) {
        if ($eintrag->created_by == $user->id) {
            $eintrag->delete();
        } else {
            $eintrag->user_id = null;
            $eintrag->save();
        }
     }

     return response()->json([
         'message' => 'Eintrag removed'], 200);
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
     * @param Request $request
     * @param $liste
     * @return \Illuminate\Http\JsonResponse
     */
    public function addEintrag(Request $request, $liste){
    $request->validate([
         'eintragung' => 'required| string',
     ]);

     $user = $request->user();

     if (!$user) {
         return response()->json(['message' => 'User not found'], 404);
     }

    $liste = Liste::findOrFail($liste);

    if ($liste->type != 'eintrag' or $liste->active == 0 or $liste->ende < now()) {
        return response()->json(['message' => 'Not allowed'], 403);
    }

    if ($liste->multiple == false) {
        $eintragungen = Listen_Eintragungen::query()
            ->where('listen_id', $liste->id)
            ->where('user_id', $user->id)
            ->count();

        if ($eintragungen > 0) {
            return response()->json(['message' => 'Not allowed'], 403);
        }
    }

     $eintrag = new Listen_Eintragungen();
     $eintrag->listen_id = $liste->id;
     $eintrag->user_id = $user->id;
     $eintrag->created_by = $user->id;
     $eintrag->eintragung = $request->eintragung;
     $eintrag->save();

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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
 {

        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->hasPermissionTo('edit terminliste', 'web')) {

            $listen = Liste::query()
                ->whereDate('ende', '>=', now())
                ->get();
        } else {
            $listen = $user->listen()
                ->whereDate('ende', '>=', now())
                ->where('active', 1)
                ->get();
        }



     $listen = $listen->unique('id');
     $listen = $listen->sortBy('listenname');

     $result = [];
     foreach ($listen as $key => $liste) {

         $result[] = $liste;

     }

        return response()->json([
            'listen' => $result], 200);
 }


    /**
     * liefert die Eintragungen einer Liste
     *
     * Liefert die Eintragungen einer Liste.
     * Wenn der User die Berechtigung hat, die Liste zu bearbeiten, werden die Namen der User angezeigt die die Eintragungen gemacht oder reserviert haben.
     * Ansonsten wird nur angezeigt, ob die Eintragung vergeben ist oder nicht.
     *
     * @group Listen
     *
     * @urlParam liste required ID der Liste
     *
     *
     * @param Request $request
     * @param $liste
     *
     * @responseField eintragungen array Eintragungen
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function getEintrag(Request $request, $liste){

        $user = $request->user();

     $eintragungen = Listen_Eintragungen::query()
         ->where('listen_id', $liste->id)
         ->get();

        foreach ($eintragungen as $key => $eintragung) {
            if ($eintragung->user_id != null) {

                if ($eintragung->user_id == $user->id) {
                    $eintragungen[$key]->user_id = 'own';
                } else {
                    if ($liste->visible_for_all == true or $user->hasPermissionTo('edit terminliste', 'web')) {
                        $eintragungen[$key]->user_id = $eintragung->user->name;
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
        }


     return response()->json(['eintragungen' => $eintragungen], 200);
 }

    /**
     * Get the appointments of a list.
     *
     * Retrieves the appointments of a list.
     * If the user has permission to edit the list, the names of the users who made or reserved the appointments are displayed.
     * Otherwise, it only shows whether the appointment is taken or not.
     *
     * @param User $user
     * @param Liste $liste
     * @return mixed
     */
    private function getTermine ($user, $liste){

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
                 } else{
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
                     $termine[$key]->reserviert_fuer = $termin->eingetragenePerson->name;

                 } else {
                     $termine[$key]->reserviert_fuer = 'vergeben';
                 }
             }
         }

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
     *
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
 {
     $user = $request->user();

     if (!$user) {
         return response()->json(['message' => 'User not found'], 404);
     }



     $liste = Liste::findOrFail($id);

     if ($liste->active == 0 or $liste->ende < now()) {
         return response()->json(['message' => 'Not allowed'], 403);
     }

     if ($liste->users->contains($user->id) == false) {
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
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelTermin(Request $request, $id)
 {
     $user = $request->user();

     if (!$user) {

         return response()->json(['message' => 'User not found'], 404);
     }

     $termin = listen_termine::findOrFail($id);

     if ($user->hasPermissionTo('edit terminliste', 'web') or $termin->liste->besitzer == $user->id or $termin->reserviert_fuer == $user->id) {
         $termin->reserviert_fuer = null;
         $termin->save();

     } else {
         return response()->json(['message' => 'Not allowed'], 403);
     }

     return response()->json([
         'message' => 'Termin canceled'], 200);
 }

    /**
     *  Termin reservieren
     *
     * Reserviert einen Termin in einer Liste für den User.
     * Wenn die Liste nur eine Reservierung pro User zulässt, wird geprüft, ob der User bereits einen Termin reserviert hat.
     *
     * @group Listen
     *
     *
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reserveTermin(Request $request, $id)
 {
     $user = $request->user();

     if (!$user) {

         return response()->json(['message' => 'User not found'], 404);
     }

     $termin = listen_termine::findOrFail($id);

     $liste = Liste::findOrFail($termin->listen_id);

     $always_reserved = listen_termine::query()
                ->where('listen_id', $liste->id)
                ->where('reserviert_fuer', $user->id)
                ->count();


     if ($termin->reserviert_fuer == null and ($termin->liste->multiple == true or $always_reserved == 0 )) {
         $termin->reserviert_fuer = $user->id;
         $termin->save();
     } else {
         return response()->json(['message' => 'Not allowed'], 403);
     }

     return response()->json([
         'message' => 'Termin reserved'], 200);
 }

}

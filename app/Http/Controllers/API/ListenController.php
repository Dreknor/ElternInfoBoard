<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Model\listen_termine;
use Illuminate\Http\Request;

/**
 *
 */
class ListenController extends Controller
{

 public function __construct()
 {
       $this->middleware('auth:sanctum');
 }

 public function reserveEintrag(Request $request, $id)
 {
     $user = $request->user();

     if (!$user) {
         return response()->json(['message' => 'User not found'], 404);
     }

     $eintrag = Listen_Eintragungen::findOrFail($id);

     $liste = Liste::findOrFail($eintrag->listen_id);

     $always_reserved = Listen_Eintragungen::query()
                ->where('listen_id', $liste->id)
                ->where('user_id', $user->id)
                ->count();

    if ($eintrag->user_id == null) {
                $eintrag->user_id = $user->id;
                $eintrag->save();
            } else {
                return response()->json(['message' => 'Not allowed'], 403);
            }

            return response()->json([
                'message' => 'Eintrag reserved'], 200);
    }

 public function removeEintrag(Request $request, $id)
 {
     $user = $request->user();

     if (!$user) {
         return response()->json(['message' => 'User not found'], 404);
     }

     $eintrag = Listen_Eintragungen::findOrFail($id);

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
 public function addEintrag(Request $request, $liste){
    $request->validate([
         'eintragung' => 'required| string',
     ]);

     $user = $request->user();

     if (!$user) {
         return response()->json(['message' => 'User not found'], 404);
     }

    $liste = Liste::findOrFail($liste);

    if ($liste->type != 'eintrag') {
        return response()->json(['message' => 'Not allowed'], 403);
    }
     $eintrag = new Listen_Eintragungen();
     $eintrag->listen_id = $liste->id;
     $eintrag->user_id = $user->id;
     $eintrag->created_by = $user->id;
     $eintrag->eintragung = $request->eintragung;
     $eintrag->save();

     return response()->json([
         'message' => 'Eintrag added'], 200);

 }


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


 public function getEintrag($user, $liste){
     $eintragungen = Listen_Eintragungen::query()
         ->where('listen_id', $liste->id)
         ->get();

        foreach ($eintragungen as $key => $eintragung) {
            if ($eintragung->user_id != null) {

                if ($eintragung->user_id == $user->id) {
                    $eintragungen[$key]->user_id = 'own';
                } else {
                    if ($liste->visible_for_all == true or $user->hasPermissionTo('edit terminliste', 'web')) {
                        $eintragungen[$key]->user_id = $eintragung->eingetragenePerson->name;
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


     return $eintragungen;
 }

 public function getTermine ($user, $liste){
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
 public function show(Request $request, $id)
 {
     $user = $request->user();

     if (!$user) {
         return response()->json(['message' => 'User not found'], 404);
     }

     $liste = Liste::findOrFail($id);

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

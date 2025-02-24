<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{


    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }


    /**
     * readAll
     *
     * Diese Methode markiert alle eigenen Benachrichtigungen als gelesen
     *
     * @group Benachrichtigungen
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function readAll(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->notifications()->update(['read' => 1]);

        return response()->json([
            'message' => 'success',
        ], 200);
    }


    /**
     * index
     *
     * Diese Methode gibt alle Benachrichtigungen des angemeldeten Benutzers zurück
     *
     * @group Benachrichtigungen
     *
     * @authenticated
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $notifications = $user->notifications()->where('read',0)->orderBy('created_at', 'desc')->get();

        return response()->json([
            'notifications' => $notifications,
        ], 200);
    }


    /**
     * Alle Benachrichtigungen eines bestimmten Typs als gelesen markieren
     *
     * Diese Methode markiert alle Benachrichtigungen eines bestimmten Typs als gelesen. <br>
     * Es gibt verschiedene Typen von Benachrichtigungen, z.B. 'Admin, Ex. Angebot, Listen Eintragung, Nachricht, Termin, Vertretung'
     *
     * @group Benachrichtigungen
     *
     * @param Request $request
     * @required type string Typ der Benachrichtigung
     * @return \Illuminate\Http\JsonResponse
     */
    public function readAllByType (Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'type' => 'required|string'
        ]);

        $user->notifications()->where('type', $request->type)->where('user_id', $user->id)->update(['read' => 1]);

        return response()->json([
            'message' => 'success',
        ], 200);
    }

    /**
     * Als gelesen markieren
     *
     * Diese Methode markiert eine bestimmte Benachrichtigung als gelesen
     *
     * @group Benachrichtigungen
     *
     * @param Request $request
     * @required id integer ID der Benachrichtigung
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function read(Request $request)
    {
        $user = $request->user();


        $request->validate([
            'id' => 'required|integer'
        ]);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }


        $notification = $user->notifications()->where('id', $request->id)->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $user->notifications()->where('type', $notification->type)->where('user_id', $user->id)->update(['read' => 1]);


        return response()->json([
            'message' => 'success',
        ], 200);
    }
}

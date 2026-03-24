<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

class NotificationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth:sanctum',
        ];
    }

    /**
     * Alle Benachrichtigungen als gelesen markieren
     *
     * Markiert alle Benachrichtigungen des authentifizierten Benutzers als gelesen.
     * Diese Aktion betrifft alle Benachrichtigungen unabhängig von ihrem Typ oder Status.
     *
     * @group Benachrichtigungen
     *
     * @authenticated
     *
     * @response 200 {
     *   "message": "success"
     * }
     *
     * @response 404 {
     *   "message": "User not found"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function readAll(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->notifications()->update(['read' => 1]);

        return response()->json([
            'message' => 'success',
        ], 200);
    }

    /**
     * Benachrichtigungen abrufen
     *
     * Gibt alle ungelesenen Benachrichtigungen des authentifizierten Benutzers zurück.
     * Die Benachrichtigungen werden nach Erstellungsdatum absteigend sortiert (neueste zuerst).
     *
     * @group Benachrichtigungen
     *
     * @authenticated
     *
     * @response 200 scenario="Erfolgreiche Abfrage" {
     *   "notifications": [
     *     {
     *       "id": 1,
     *       "user_id": 42,
     *       "type": "Nachricht",
     *       "title": "Neue Nachricht erhalten",
     *       "message": "Sie haben eine neue Nachricht von Max Mustermann",
     *       "data": null,
     *       "read": 0,
     *       "created_at": "2026-02-19T10:30:00.000000Z",
     *       "updated_at": "2026-02-19T10:30:00.000000Z"
     *     },
     *     {
     *       "id": 2,
     *       "user_id": 42,
     *       "type": "Termin",
     *       "title": "Terminänderung",
     *       "message": "Der Termin am 25.02.2026 wurde verschoben",
     *       "data": null,
     *       "read": 0,
     *       "created_at": "2026-02-18T14:15:00.000000Z",
     *       "updated_at": "2026-02-18T14:15:00.000000Z"
     *     }
     *   ]
     * }
     *
     * @response 404 scenario="Benutzer nicht gefunden" {
     *   "message": "User not found"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $notifications = $user->notifications()->where('read', 0)->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $notifications,
        ], 200);
    }

    /**
     * Benachrichtigungen nach Typ als gelesen markieren
     *
     * Markiert alle Benachrichtigungen eines bestimmten Typs für den authentifizierten Benutzer als gelesen.
     * Dies ermöglicht es, alle Benachrichtigungen einer Kategorie auf einmal zu markieren.
     *
     * Verfügbare Benachrichtigungstypen:
     * - Admin
     * - Ex. Angebot
     * - Listen Eintragung
     * - Nachricht
     * - Termin
     * - Vertretung
     *
     * @group Benachrichtigungen
     *
     * @authenticated
     *
     * @bodyParam type string required Der Typ der Benachrichtigungen, die als gelesen markiert werden sollen. Example: Nachricht
     *
     * @response 200 scenario="Erfolgreiche Markierung" {
     *   "message": "success"
     * }
     *
     * @response 404 scenario="Benutzer nicht gefunden" {
     *   "message": "User not found"
     * }
     *
     * @response 422 scenario="Validierungsfehler" {
     *   "message": "The type field is required.",
     *   "errors": {
     *     "type": [
     *       "The type field is required."
     *     ]
     *   }
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function readAllByType(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'type' => 'required|string',
        ]);

        $user->notifications()->where('type', $request->type)->where('user_id', $user->id)->update(['read' => 1]);

        return response()->json([
            'message' => 'success',
        ], 200);
    }

    /**
     * Einzelne Benachrichtigung als gelesen markieren
     *
     * Markiert eine bestimmte Benachrichtigung anhand ihrer ID als gelesen.
     * Diese Methode markiert automatisch alle Benachrichtigungen desselben Typs als gelesen,
     * um konsistente Gruppenmarkierungen zu gewährleisten.
     *
     * @group Benachrichtigungen
     *
     * @authenticated
     *
     * @bodyParam id integer required Die ID der Benachrichtigung, die als gelesen markiert werden soll. Example: 1
     *
     * @response 200 scenario="Erfolgreiche Markierung" {
     *   "message": "success"
     * }
     *
     * @response 404 scenario="Benutzer nicht gefunden" {
     *   "message": "User not found"
     * }
     *
     * @response 404 scenario="Benachrichtigung nicht gefunden" {
     *   "message": "Notification not found"
     * }
     *
     * @response 422 scenario="Validierungsfehler" {
     *   "message": "The id field is required.",
     *   "errors": {
     *     "id": [
     *       "The id field is required."
     *     ]
     *   }
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function read(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'id' => 'required|integer',
        ]);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $notification = $user->notifications()->where('id', $request->id)->first();

        if (! $notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $user->notifications()->where('type', $notification->type)->where('user_id', $user->id)->update(['read' => 1]);

        return response()->json([
            'message' => 'success',
        ], 200);
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPermissionsController extends Controller
{
    /**
     * Berechtigungen und Rollen des angemeldeten Nutzers abrufen
     *
     * Gibt alle Berechtigungen (direkt vergeben und über Rollen geerbt)
     * sowie alle Rollennamen des authentifizierten Benutzers zurück.
     *
     * @group Benutzer
     *
     * @authenticated
     *
     * @response 200 scenario="Erfolgreiche Abfrage" {
     *   "success": true,
     *   "data": {
     *     "permissions": [
     *       "view vertretungsplan",
     *       "use messenger"
     *     ],
     *     "roles": [
     *       "Eltern"
     *     ]
     *   }
     * }
     *
     * @response 401 scenario="Nicht authentifiziert" {
     *   "message": "Unauthenticated."
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data'    => [
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
                'roles'       => $user->getRoleNames()->values(),
            ],
        ]);
    }
}


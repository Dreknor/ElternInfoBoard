<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Module;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    /**
     * Aktive Module des angemeldeten Nutzers abrufen
     *
     * Gibt alle Module zurück, auf die der authentifizierte Benutzer Zugriff hat.
     * Module, die spezifische Berechtigungen erfordern (options.rights), werden nur
     * zurückgegeben, wenn der Benutzer mindestens eine dieser Berechtigungen besitzt.
     * Die vollständigen Optionen (Nav-Links etc.) werden nicht zurückgegeben – nur der
     * aktive Status.
     *
     * @group Module
     *
     * @authenticated
     *
     * @response 200 scenario="Erfolgreiche Abfrage" {
     *   "success": true,
     *   "data": [
     *     {
     *       "setting": "Listen",
     *       "category": "Module",
     *       "description": "Termin- und Eintragslisten",
     *       "options": { "active": true }
     *     },
     *     {
     *       "setting": "Vertretungsplan",
     *       "category": "Module",
     *       "description": "Vertretungsplan-Ansicht",
     *       "options": { "active": true }
     *     }
     *   ]
     * }
     *
     * @response 401 scenario="Nicht authentifiziert" {
     *   "message": "Unauthenticated."
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $modules = Module::where('category', 'module')
            ->get()
            ->filter(function (Module $module) use ($user) {
                $rights = $module->options['rights'] ?? [];
                if (is_string($rights)) {
                    $rights = json_decode($rights, true) ?? [];
                }

                // Kein Recht erforderlich → für jeden sichtbar
                if (empty($rights)) {
                    return true;
                }

                // Benutzer muss mindestens eine der geforderten Berechtigungen besitzen
                foreach ($rights as $right) {
                    if ($user->can($right)) {
                        return true;
                    }
                }

                return false;
            })
            ->map(function (Module $module) {
                $rawActive = $module->options['active'] ?? false;
                $active    = $rawActive === '1' || $rawActive === 1 || $rawActive === true;

                return [
                    'setting'     => $module->setting,
                    'category'    => ucfirst($module->category),
                    'description' => $module->description,
                    'options'     => ['active' => $active],
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $modules,
        ]);
    }
}


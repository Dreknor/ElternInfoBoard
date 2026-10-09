<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\StoreUserSettingsRequest;
use App\Http\Requests\API\UpdateUserSettingsRequest;
use App\Model\UserAppSettings;
use App\Services\Notifications\NotificationPreferences;
use App\Services\UserAppSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * App-spezifische Einstellungen des angemeldeten Users.
 *
 * @group Einstellungen
 *
 * @authenticated
 */
class UserSettingsController extends Controller
{
    /**
     * Einstellungen abrufen
     *
     * Liefert die gespeicherten App-Einstellungen. Ohne gespeicherte Einstellungen antwortet der Endpunkt mit 404 und `use_defaults: true`.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        /** @var \App\Model\User $user */
        $user = Auth::user();
        $settings = UserAppSettings::where('user_id', $user->id)->first();

        if (!$settings) {
            return response()->json([
                'success' => false,
                'message' => 'No settings found. Using defaults.',
                'data' => [
                    'settings' => null,
                    'use_defaults' => true,
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'settings' => $settings->settings,
                'updated_at' => $settings->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Einstellungen speichern
     *
     * Legt die Einstellungen an oder ersetzt sie vollständig.
     *
     * @param StoreUserSettingsRequest $request
     * @return JsonResponse
     */
    public function store(StoreUserSettingsRequest $request): JsonResponse
    {
        /** @var \App\Model\User $user */
        $user = Auth::user();
        $validated = $request->validated();

        $settings = UserAppSettings::updateOrCreate(
            ['user_id' => $user->id],
            ['settings' => $validated['settings']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Settings saved successfully',
            'data' => [
                'settings' => $settings->settings,
                'updated_at' => $settings->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Einzelne Einstellung ändern
     *
     * Setzt den Wert unter `path` (Punkt-Notation, z. B. `push.posts`). Fehlen Einstellungen, werden zuvor die Standardwerte angelegt.
     *
     * @param UpdateUserSettingsRequest $request
     * @return JsonResponse
     */
    public function update(UpdateUserSettingsRequest $request): JsonResponse
    {
        /** @var \App\Model\User $user */
        $user = Auth::user();
        $validated = $request->validated();

        $settings = UserAppSettings::where('user_id', $user->id)->first();

        if (!$settings) {
            // Erstelle Settings mit Defaults, wenn noch keine vorhanden
            $settings = UserAppSettings::create([
                'user_id' => $user->id,
                'settings' => UserAppSettingsService::getDefaultSettings(),
            ]);
        }

        // Update the specific path
        $settings->setSettingByPath($validated['path'], $validated['value']);
        $settings->save();

        // Ältere App-Versionen schalten App-Push über `push.<kategorie>` – in die Kanalwahl übernehmen.
        if (preg_match('/^push\.([a-z]+)$/', $validated['path'], $m) && is_bool($validated['value'])) {
            NotificationPreferences::set($user->id, $m[1], 'app', $validated['value']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => [
                'settings' => $settings->settings,
                'updated_at' => $settings->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Einstellungen zurücksetzen
     *
     * Löscht die gespeicherten Einstellungen; danach gelten die Standardwerte.
     *
     * @return JsonResponse
     */
    public function destroy(): JsonResponse
    {
        /** @var \App\Model\User $user */
        $user = Auth::user();
        $settings = UserAppSettings::where('user_id', $user->id)->first();

        if (!$settings) {
            return response()->json([
                'success' => false,
                'message' => 'No settings found to delete',
            ], 404);
        }

        $settings->delete();

        return response()->json([
            'success' => true,
            'message' => 'Settings deleted successfully. Defaults will be used.',
        ]);
    }

    /**
     * Standardeinstellungen abrufen
     *
     * @return JsonResponse
     */
    public function defaults(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'settings' => UserAppSettingsService::getDefaultSettings(),
            ],
        ]);
    }
}







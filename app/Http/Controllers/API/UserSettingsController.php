<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\StoreUserSettingsRequest;
use App\Http\Requests\API\UpdateUserSettingsRequest;
use App\Model\UserAppSettings;
use App\Services\UserAppSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserSettingsController extends Controller
{
    /**
     * Display the authenticated user's settings.
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
     * Store or update the user's settings.
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
     * Partially update the user's settings.
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
     * Remove the user's settings (reset to defaults).
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
     * Get the default settings.
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







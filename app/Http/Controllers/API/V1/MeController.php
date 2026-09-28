<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ChildResource;
use App\Http\Resources\V1\UserResource;
use App\Model\UserDevice;
use App\Services\App\AppTheme;
use App\Services\App\Family;
use App\Services\App\MessengerUnread;
use App\Services\App\Modules;
use App\Services\App\TodoService;
use App\Services\ThemeService;
use App\Settings\GeneralSetting;
use App\Settings\SchickzeitenSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * @group App: Profil
 */
class MeController extends Controller
{
    /**
     * Alles für den App-Start in einer Anfrage (B-02).
     *
     * Nutzer, Rechte, Module, Kinder, Zähler, Einstellungen, Theme und Logos.
     */
    public function bootstrap(Request $request, ThemeService $themes, TodoService $todos, GeneralSetting $general, SchickzeitenSetting $schicken): JsonResponse
    {
        $user = $request->user();
        $children = \App\Model\Child::with(['group', 'class'])->whereIn('id', Family::childIds($user))->orderBy('first_name')->get();
        $modules = Modules::activeFor($user);

        return response()->json(['data' => [
            'user' => new UserResource($user),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            'roles' => $user->getRoleNames()->values(),
            'modules' => $modules,
            'children' => ChildResource::collection($children),
            'counters' => [
                'notifications' => $user->notifications()->where('read', false)->count(),
                'messenger' => in_array('Eltern-Nachrichten', $modules, true) && $user->can('use messenger')
                    ? MessengerUnread::total($user->id) : 0,
                'todo' => $user->changePassword ? 0 : count($todos->forUser($user)),
            ],
            'settings' => [
                'schickzeiten' => ['ab' => $schicken->schicken_ab, 'bis' => $schicken->schicken_bis],
                'krankmeldung_kommentar_pflicht' => false,
            ],
            'theme' => AppTheme::from($themes->resolveActive()),
            'logo_url' => InstanceController::imageUrl($general->logo, 'logo.png'),
            'icon_url' => InstanceController::imageUrl($general->favicon, 'app_logo.png'),
            'school_name' => $general->app_name ?: config('app.name'),
            'ical_url' => $user->releaseCalendar ? url($user->uuid.'/ical') : null,
        ]]);
    }

    /**
     * Eigenes Profil.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => new UserResource($request->user())]);
    }

    /**
     * Profil ändern (B-70).
     *
     * Name und E-Mail ändert die Schule; hier nur Kontakt- und Benachrichtigungseinstellungen.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'sometimes|nullable|string|max:50',
            'public_mail' => 'sometimes|nullable|email|max:255',
            'public_phone' => 'sometimes|nullable|string|max:50',
            'benachrichtigung' => 'sometimes|in:daily,weekly',
            'send_copy' => 'sometimes|boolean',
            'release_calendar' => 'sometimes|boolean',
            'calendar_prefix' => 'sometimes|nullable|string|max:8',
            'messenger_discoverable' => 'sometimes|boolean',
        ]);

        $map = [
            'public_mail' => 'publicMail',
            'public_phone' => 'publicPhone',
            'send_copy' => 'sendCopy',
            'release_calendar' => 'releaseCalendar',
        ];
        $user = $request->user();
        foreach ($data as $key => $value) {
            $user->{$map[$key] ?? $key} = $value;
        }
        $user->save();

        return response()->json(['data' => new UserResource($user->fresh())]);
    }

    /**
     * Passwort ändern.
     */
    public function password(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|current_password:sanctum',
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ], [
            'current_password.current_password' => 'Das aktuelle Passwort ist nicht korrekt.',
        ]);

        $user = $request->user();
        $user->update(['password' => Hash::make($request->password), 'changePassword' => false]);

        // Andere Sitzungen (Tokens) beenden, das aktuelle bleibt.
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Passwort geändert.']);
    }

    /**
     * Angemeldete Geräte (Tokens).
     */
    public function tokens(Request $request): JsonResponse
    {
        $current = $request->user()->currentAccessToken()->id;

        return response()->json(['data' => $request->user()->tokens()->latest()->get()->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'last_used_at' => $t->last_used_at?->toIso8601String(),
            'created_at' => $t->created_at?->toIso8601String(),
            'current' => $t->id === $current,
        ])]);
    }

    public function destroyToken(Request $request, int $id): JsonResponse
    {
        $request->user()->tokens()->where('id', $id)->delete();

        return response()->json(['message' => 'Gerät abgemeldet.']);
    }

    /**
     * Push-Gerät registrieren (B-04). Ein Token gehört immer genau einem Nutzer.
     */
    public function storeDevice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string|max:512',
            'provider' => 'required|in:fcm,apns',
            'platform' => 'nullable|string|max:20',
            'device_name' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:20',
        ]);

        UserDevice::updateOrCreate(
            ['token' => $data['token']],
            $data + ['user_id' => $request->user()->id, 'last_seen_at' => now()]
        );

        return response()->json(['message' => 'Gerät registriert.'], 201);
    }

    public function destroyDevice(Request $request, string $token): JsonResponse
    {
        UserDevice::where('user_id', $request->user()->id)->where('token', $token)->delete();

        return response()->json(['message' => 'Gerät entfernt.']);
    }

    /**
     * Datenschutz-Auskunft: gespeicherte Daten als JSON (B-71).
     */
    public function datenschutz(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(['data' => [
            'profil' => new UserResource($user),
            'gruppen' => $user->groups()->pluck('name'),
            'kinder' => Family::children($user)->map(fn ($c) => trim($c->first_name.' '.$c->last_name))->values(),
            'rueckmeldungen' => $user->userRueckmeldung()->count(),
            'lesebestaetigungen' => $user->read_receipts()->count(),
            'krankmeldungen' => $user->krankmeldungen()->count(),
            'pflichtstunden' => $user->pflichtstunden()->count(),
            'geraete' => UserDevice::where('user_id', $user->id)->get(['platform', 'device_name', 'last_seen_at']),
            'angemeldet_seit' => $user->created_at?->toIso8601String(),
        ]])->header('Cache-Control', 'no-store');
    }
}

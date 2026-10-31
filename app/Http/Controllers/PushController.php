<?php

namespace App\Http\Controllers;

use App\Model\User;
use App\Notifications\Push;
use App\Model\PushSubscription;
use App\Services\Push\BrowserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PushController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }

    /**
     * Testen der Push-Benachrichtigung
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function testPush()
    {

        if (auth()->user()->can('testing')) {
            Log::info('PushController:testPush: Benachrichtigung wird gesendet an '.auth()->user()->name);
            auth()->user()->notify(new Push('Testbenachrichtigung', 'Dies ist eine Testbenachrichtigung'));

            return redirect()->back()->with([
                'Meldung' => 'Benachrichtigung wurde gesendet',
                'type' => 'success',
            ]);
        }

        return redirect()->back()->with([
            'Meldung' => 'Keine Berechtigung',
            'type' => 'danger',
        ]);

    }

    /**
     * Store the PushSubscription.
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'endpoint' => 'required',
            'keys.auth' => 'required',
            'keys.p256dh' => 'required',
            'platform' => 'nullable|string|max:20',
        ]);

        $endpoint = $request->endpoint;
        $token = $request->keys['auth'];
        $key = $request->keys['p256dh'];
        $user = auth()->user();
        $subscription = $user->updatePushSubscription($endpoint, $key, $token);

        // Gerät merken: für die Verwaltung der Abos und damit auf Geräten mit App kein doppelter Push kommt
        $platform = BrowserDevice::platform($request->input('platform'), $request->userAgent());
        $subscription->forceFill([
            'platform' => $platform,
            'device_name' => BrowserDevice::name($request->userAgent(), $platform),
            'last_seen_at' => now(),
        ])->save();

        return response()->json(['success' => true]);
    }

    /**
     * Browser-Abo eines Geräts entfernen (Einstellungen → Benachrichtigungen).
     */
    public function destroy(PushSubscription $subscription): RedirectResponse
    {
        abort_unless(auth()->user()->ownsPushSubscription($subscription), 404);
        $subscription->delete();

        return redirect()->back()->with([
            'Meldung' => 'Browser-Push für „'.($subscription->device_name ?? 'Unbekanntes Gerät').'“ wurde entfernt.',
            'type' => 'success',
        ]);
    }

    public function push(User $user)
    {

        if (auth()->user()->can('testing')) {
            Log::info('PushController:push: Benachrichtigung wird gesendet an '.$user->name);
            Notification::send($user, new Push('Testbenachrichtigung', 'Dies ist eine Testbenachrichtigung'));

            return redirect()->back()->with([
                'Meldung' => 'Benachrichtigung wurde gesendet',
                'type' => 'success',
            ]);
        }

        return redirect()->back()->with([
            'Meldung' => 'Keine Berechtigung',
            'type' => 'danger',
        ]);

    }
}

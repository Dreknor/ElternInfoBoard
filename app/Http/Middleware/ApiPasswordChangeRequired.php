<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Erzwungener Passwortwechsel gilt auch für App-Tokens (B-08): Solange `changePassword` gesetzt ist,
 * sind nur Anmeldung, Profil, Passwortänderung und Abmelden erreichbar.
 */
class ApiPasswordChangeRequired
{
    private const ALLOWED = [
        'api.v1.bootstrap',
        'api.v1.me',
        'api.v1.me.password',
        'api.v1.token.logout',
        'api.v1.devices.store',
        'api.v1.devices.destroy',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->changePassword && ! in_array($request->route()?->getName(), self::ALLOWED, true)) {
            return response()->json([
                'message' => 'Bitte ändern Sie zuerst Ihr Passwort.',
                'code' => 'password_change_required',
            ], 403);
        }

        return $next($request);
    }
}

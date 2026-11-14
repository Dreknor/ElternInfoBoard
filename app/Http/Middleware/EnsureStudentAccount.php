<?php

namespace App\Http\Middleware;

use App\Model\User;
use App\Support\StudentAccounts;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Schülerrouten (`student.*`, `api.v1.student.*`) nur für Schülerkonten bei aktivem Modul.
 * Für alle anderen gibt es sie nicht (404), damit ausgeschaltet nichts auf Schülerkonten hinweist.
 */
class EnsureStudentAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! StudentAccounts::enabled() || ! $user instanceof User || ! $user->isStudent()) {
            abort(404);
        }

        return $next($request);
    }
}

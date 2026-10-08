<?php

namespace App\Http\Middleware;

use App\Services\App\Modules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sperrt Routen eines Moduls, solange das Modul nicht aktiv bzw. für den Nutzer
 * nicht freigegeben ist (gleiche Regeln wie Navigation und `GET /api/modules`).
 *
 * Verwendung: ->middleware('module:Reinigung')
 */
class EnsureModuleActive
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (! $user || ! Modules::isActiveFor($user, $module)) {
            abort(404);
        }

        return $next($request);
    }
}

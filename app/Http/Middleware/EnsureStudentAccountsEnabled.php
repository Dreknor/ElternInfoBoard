<?php

namespace App\Http\Middleware;

use App\Support\StudentAccounts;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verwaltungs- und Eltern-Routen der Schülerkonten gibt es nur bei aktivem Modul (sonst 404).
 */
class EnsureStudentAccountsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(StudentAccounts::enabled(), 404);

        return $next($request);
    }
}

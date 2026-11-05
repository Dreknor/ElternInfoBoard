<?php

namespace App\Http\Middleware;

use App\Services\Students\StudentMessenger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Chat Schüler ↔ Lehrkräfte nur bei aktivem Modul und Einstellung (S-14), sonst 404. */
class EnsureStudentMessengerEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(StudentMessenger::enabled(), 404);

        return $next($request);
    }
}

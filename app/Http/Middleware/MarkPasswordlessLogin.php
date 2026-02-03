<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Closure;
use Illuminate\Http\Request;

class MarkPasswordlessLogin
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Mark this session as coming from passwordless login
        $request->session()->put('passwordless_login', true);

        return $next($request);
    }
}

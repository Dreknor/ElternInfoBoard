<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MarkPasswordlessLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Mark this session as coming from passwordless login
        $request->session()->put('passwordless_login', true);

        return $next($request);
    }
}


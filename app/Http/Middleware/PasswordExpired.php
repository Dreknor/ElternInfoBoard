<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Closure;

class PasswordExpired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip password change enforcement for passwordless login
        if ($request->session()->has('passwordless_login')) {
            return $next($request);
        }

        if ($user->changePassword and ! $request->session()->has('ownID')) {
            return redirect()->route('password.expired');
        }

        return $next($request);
    }
}

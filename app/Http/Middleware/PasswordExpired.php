<?php

namespace App\Http\Middleware;

use Closure;

class PasswordExpired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
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

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class LastOnlineAt
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (auth()->guest() or $request->session()->has('ownID')) {
            return $next($request);
        }
        if ($request->user()->last_online_at->diffInMinutes(now()) <= 5 and $request->user()->track_login == true) {
            DB::table('users')
                ->where('id', $request->user()->id)
                ->update(['last_online_at' => now()]);
        }

        return $next($request);
    }
}

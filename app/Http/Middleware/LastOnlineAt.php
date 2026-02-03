<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LastOnlineAt
{
    /**
     * Handle an incoming request.
     *
     * @param  Illuminate\Support\Facades\Request  $request
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->guest() or session()->has('ownID')) {
            return $next($request);
        }
        if (auth()->user()->last_online_at->diffInMinutes(now()) >= 5 and auth()->user()->track_login == true) {
            DB::table('users')
                ->where('id', auth()->user()->id)
                ->update(['last_online_at' => now()]);
        }

        return $next($request);
    }
}

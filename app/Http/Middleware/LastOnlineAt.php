<?php

namespace App\Http\Middleware;

use App\Settings\GeneralSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LastOnlineAt
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Breche früh ab, wenn der Nutzer ein Gast ist oder unter fremder ID agiert
        if (! $user || $request->session()->has('ownID')) {
            return $next($request);
        }

        $mode = app(GeneralSetting::class)->login_tracking_mode ?? 'user';

        $shouldTrack = match ($mode) {
            'always' => true,
            'never'  => false,
            default  => (bool) $user->track_login, // 'user' – Nutzer entscheidet
        };

        if ($shouldTrack && (! $user->last_online_at || $user->last_online_at->diffInMinutes(now()) >= 5)) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['last_online_at' => now()]);
        }

        return $next($request);
    }
}

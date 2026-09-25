<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wiederholte schreibende Anfragen mit gleichem `Idempotency-Key` werden nicht erneut ausgeführt,
 * sondern erhalten die gespeicherte Antwort (24 h). Schützt vor doppelten Krankmeldungen,
 * Rückmeldungen usw., wenn die App bei schlechtem Netz erneut sendet.
 */
class IdempotencyKey
{
    private const TTL_SECONDS = 86400;

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');
        $user = $request->user();

        if (! $key || ! $user || $request->isMethodSafe() || strlen($key) > 100) {
            return $next($request);
        }

        $cacheKey = 'idem:'.$user->id.':'.sha1($key.'|'.$request->method().'|'.$request->path());

        if ($cached = Cache::get($cacheKey)) {
            return response($cached['body'], $cached['status'], [
                'Content-Type' => $cached['type'],
                'Idempotent-Replayed' => 'true',
            ]);
        }

        $lock = Cache::lock($cacheKey.':lock', 30);
        if (! $lock->get()) {
            return response()->json(['message' => 'Die Anfrage wird bereits verarbeitet.'], 409);
        }

        try {
            $response = $next($request);
            // Serverfehler nicht speichern – die App soll es erneut versuchen dürfen.
            if ($response->getStatusCode() < 500) {
                Cache::put($cacheKey, [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getContent(),
                    'type' => $response->headers->get('Content-Type', 'application/json'),
                ], self::TTL_SECONDS);
            }

            return $response;
        } finally {
            $lock->release();
        }
    }
}

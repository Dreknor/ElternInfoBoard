<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ETag für GET-Antworten: unveränderte Daten werden mit `304 Not Modified` beantwortet,
 * die App nutzt dann ihren Cache (spart Datenvolumen und Parse-Zeit).
 */
class ETagResponses
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200
            || ! str_contains((string) $response->headers->get('Content-Type'), 'json')) {
            return $response;
        }

        $etag = '"'.md5((string) $response->getContent()).'"';
        $response->headers->set('ETag', $etag);
        $response->headers->set('Cache-Control', 'private, no-cache');

        $sent = array_map('trim', explode(',', (string) $request->header('If-None-Match')));
        if (in_array($etag, $sent, true)) {
            $response->setNotModified();
        }

        return $response;
    }
}

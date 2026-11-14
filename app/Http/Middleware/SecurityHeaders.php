<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Grundlegende Sicherheits-Header für alle Antworten.
 *
 * Bereits gesetzte Header (z. B. die Sandbox-CSP bei Datei-Downloads) werden nicht überschrieben.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            // Browser dürfen den Content-Type nicht „erraten“ (verhindert Skriptausführung in Uploads)
            'X-Content-Type-Options' => 'nosniff',
            // Kein Einbetten in fremde Seiten (Clickjacking)
            'X-Frame-Options' => 'SAMEORIGIN',
            // Keine vollständigen URLs (mit IDs/Tokens) an fremde Seiten weitergeben
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            // Zweite Verteidigungslinie: keine Plugins, kein <base>-Hijacking, kein Framing von außen
            'Content-Security-Policy' => "object-src 'none'; base-uri 'self'; frame-ancestors 'self'",
        ];

        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000';
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}

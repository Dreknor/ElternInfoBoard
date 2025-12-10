<?php

namespace App\Http\Middleware;

use App\Settings\GeneralSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ValidateFaviconExists
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
        try {
            $settings = new GeneralSetting();

            // Prüfe, ob das Favicon existiert (nur wenn es nicht das Standard-Logo ist)
            if ($settings->favicon && $settings->favicon !== 'app_logo.png') {
                if (!Storage::disk('public')->exists('img/' . $settings->favicon)) {
                    Log::warning('Favicon nicht gefunden: ' . $settings->favicon . '. Setze auf Standard-Logo zurück.');

                    // Setze Favicon auf Standard-Logo zurück
                    $settings->favicon = 'app_logo.png';
                    $settings->save();
                }
            }

            // Prüfe, ob das Logo existiert (nur wenn es nicht das Standard-Logo ist)
            if ($settings->logo && $settings->logo !== 'app_logo.png') {
                if (!Storage::disk('public')->exists('img/' . $settings->logo)) {
                    Log::warning('Logo nicht gefunden: ' . $settings->logo . '. Setze auf Standard-Logo zurück.');

                    // Setze Logo auf Standard-Logo zurück
                    $settings->logo = 'app_logo.png';
                    $settings->save();
                }
            }
        } catch (\Exception $e) {
            // Fehler loggen, aber nicht die Anfrage blockieren
            Log::error('Fehler bei Favicon-Validierung: ' . $e->getMessage());
        }

        return $next($request);
    }
}


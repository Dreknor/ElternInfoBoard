<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Einrichtung der Eltern-App per QR-Code aus den Web-Einstellungen:
 * Der Code enthält die Schuladresse und einen einmaligen Anmeldecode (10 min gültig),
 * den die App über `POST /api/v1/auth/exchange` gegen ein Token tauscht.
 */
class AppConnectController extends Controller
{
    private const TTL_MINUTES = 10;

    public function qr(Request $request): JsonResponse
    {
        $user = $request->user();

        // Nur ein gültiger Code pro Nutzer: der vorherige verfällt.
        if ($previous = Cache::pull("app_qr_code_of:{$user->id}")) {
            Cache::forget("app_login_code:{$previous}");
        }

        $code = Str::random(48);
        $expires = now()->addMinutes(self::TTL_MINUTES);
        Cache::put("app_login_code:{$code}", ['user_id' => $user->id, 'challenge' => null], $expires);
        Cache::put("app_qr_code_of:{$user->id}", $code, $expires);

        $content = config('services.app.scheme').'://connect?'.http_build_query([
            'server' => rtrim(config('app.url'), '/'),
            'code' => $code,
        ]);

        $writer = new Writer(new ImageRenderer(new RendererStyle(240, 1), new SvgImageBackEnd));

        return response()->json([
            'svg' => $writer->writeString($content),
            'expires_at' => $expires->toIso8601String(),
            'expires_at_label' => $expires->format('H:i'),
        ])->header('Cache-Control', 'no-store');
    }
}

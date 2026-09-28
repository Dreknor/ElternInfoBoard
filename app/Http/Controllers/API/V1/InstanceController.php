<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\App\AppTheme;
use App\Services\ThemeService;
use App\Settings\GeneralSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * @group App: Anmeldung
 */
class InstanceController extends Controller
{
    /**
     * Informationen über die Schule (ohne Anmeldung).
     *
     * Schulname, Logos, Farben des Standard-Themes und angebotene Anmeldearten – die App
     * nutzt das für Serverwahl und Anmeldebildschirm (B-01).
     *
     * @unauthenticated
     */
    public function show(GeneralSetting $settings, ThemeService $themes): JsonResponse
    {
        return response()->json([
            'data' => [
                'name' => $settings->app_name ?: config('app.name'),
                'logo_url' => self::imageUrl($settings->logo, 'logo.png'),
                'icon_url' => self::imageUrl($settings->favicon, 'app_logo.png'),
                'theme' => AppTheme::from($themes->registry()->get($themes->defaultTheme())),
                'auth' => [
                    'password' => true,
                    'sso' => (bool) config('services.keycloak.enabled'),
                    'sso_label' => config('services.keycloak.button_text', 'Mit Schulkonto anmelden'),
                    'magic_link' => true,
                ],
                'api_version' => 1,
                'min_app_version' => config('services.app.min_version'),
            ],
        ])->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * Logo-URL wie im Web-Layout: Standarddatei aus public/img, hochgeladene aus storage/img.
     * `?v=` ändert sich bei neuem Logo – die App lädt es dann neu.
     */
    public static function imageUrl(?string $file, string $default): ?string
    {
        if ($file && $file !== $default && Storage::disk('public')->exists('img/'.$file)) {
            return url('storage/img/'.$file).'?v='.Storage::disk('public')->lastModified('img/'.$file);
        }

        $path = public_path('img/'.$default);

        return file_exists($path) ? asset('img/'.$default).'?v='.filemtime($path) : null;
    }
}

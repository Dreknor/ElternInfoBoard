<?php

namespace App\Http\Middleware;

use App\Support\BasePath;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Betrieb in einem Unterverzeichnis (z. B. …/elterninfoboard/public/):
 * Livewire-Script unter dem Installationspfad einbinden. Die Update-URL für Livewire
 * und window.appUrl() setzt resources/views/include/base-path.blade.php.
 *
 * Eine selbst gesetzte livewire.asset_url (config/livewire.php) hat Vorrang.
 */
class ApplyBasePath
{
    protected static ?string $configuredAssetUrl = null;

    public function handle(Request $request, Closure $next): Response
    {
        static::$configuredAssetUrl ??= (string) config('livewire.asset_url');

        if (static::$configuredAssetUrl === '') {
            config(['livewire.asset_url' => BasePath::get() !== '' ? BasePath::livewireScriptUrl() : null]);
        }

        return $next($request);
    }
}

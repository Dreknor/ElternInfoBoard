<?php

namespace App\Http\Middleware;

use App\Services\ThemeService;
use Closure;
use Illuminate\Http\Request;

class ApplyTheme
{
    public function __construct(private ThemeService $themeService) {}

    public function handle(Request $request, Closure $next)
    {
        $theme = $this->themeService->resolveActive();

        // Theme-Daten in allen Views verfügbar machen
        view()->share('activeTheme', $theme);
        view()->share('themeService', $this->themeService);

        return $next($request);
    }
}


<?php

namespace App\View\Components;

use App\Services\ThemeService;
use Illuminate\View\Component;

class ThemeVars extends Component
{
    public string $cssVars;

    public string $bodyClasses;

    public function __construct(ThemeService $themeService)
    {
        $theme = $themeService->resolveActive();

        $vars = collect($theme->variables())
            ->map(fn ($v, $k) => "  {$k}: {$v};")
            ->implode("\n");

        $this->cssVars = ":root {\n{$vars}\n}";
        $this->bodyClasses = $theme->bodyClasses();
    }

    public function render()
    {
        return view('components.theme-vars');
    }
}


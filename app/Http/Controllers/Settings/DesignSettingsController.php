<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\NavigationService;
use App\Settings\GeneralSetting;
use App\Themes\ThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DesignSettingsController extends Controller
{
    public function update(Request $request, GeneralSetting $settings, ThemeRegistry $registry, NavigationService $navigation): RedirectResponse
    {
        $validated = $request->validate([
            'default_theme' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($registry) {
                    if (! $registry->exists((string) $value)) {
                        $fail('Ungültiges Theme.');
                    }
                },
            ],
            'allow_user_theme' => 'nullable|boolean',
            'nav_style' => ['nullable', 'string', Rule::in(array_keys($navigation->styles()))],
            'allow_user_nav_style' => 'nullable|boolean',
        ]);

        $settings->default_theme    = $validated['default_theme'];
        $settings->allow_user_theme = $request->boolean('allow_user_theme');

        // Formulare ohne Menü-Felder lassen die Menü-Einstellungen unverändert
        if ($request->has('nav_style')) {
            $settings->nav_style            = $validated['nav_style'] ?? NavigationService::STYLE_CLASSIC;
            $settings->allow_user_nav_style = $request->boolean('allow_user_nav_style');
        }

        $settings->save();

        return back()->with([
            'type'    => 'success',
            'Meldung' => 'Design-Einstellungen gespeichert.',
        ]);
    }
}

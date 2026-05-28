<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Settings\CustomThemeSetting;
use App\Themes\DefaultTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomThemeController extends Controller
{
    public function update(Request $request, CustomThemeSetting $setting): RedirectResponse
    {
        $request->validate([
            'custom_theme_name'        => 'required|string|max:100',
            'custom_theme_description' => 'nullable|string|max:300',
            'vars'                     => 'nullable|array',
        ]);

        $setting->name        = $request->input('custom_theme_name', 'Eigenes Design');
        $setting->description = $request->input('custom_theme_description', '');

        // Nur Werte speichern, die vom Default abweichen
        $defaults    = (new DefaultTheme())->variables();
        $submitted   = $request->input('vars', []);
        $customVars  = [];

        foreach ($submitted as $key => $value) {
            $value = trim((string) $value);
            if ($value !== '' && $value !== ($defaults[$key] ?? '')) {
                $customVars[$key] = $value;
            }
        }

        $setting->variables = $customVars;
        $setting->save();

        return back()->with([
            'type'    => 'success',
            'Meldung' => 'Eigenes Design gespeichert.',
        ]);
    }

    /**
     * Setzt alle Variablen auf Default-Werte zurück.
     */
    public function reset(CustomThemeSetting $setting): RedirectResponse
    {
        $setting->variables = [];
        $setting->save();

        return back()->with([
            'type'    => 'success',
            'Meldung' => 'Eigenes Design auf Standard-Werte zurückgesetzt.',
        ]);
    }
}


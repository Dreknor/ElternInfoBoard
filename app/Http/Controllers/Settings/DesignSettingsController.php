<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Settings\GeneralSetting;
use App\Themes\ThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DesignSettingsController extends Controller
{
    public function update(Request $request, GeneralSetting $settings, ThemeRegistry $registry): RedirectResponse
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
        ]);

        $settings->default_theme    = $validated['default_theme'];
        $settings->allow_user_theme = $request->boolean('allow_user_theme');
        $settings->save();

        return back()->with([
            'type'    => 'success',
            'Meldung' => 'Design-Einstellungen gespeichert.',
        ]);
    }
}



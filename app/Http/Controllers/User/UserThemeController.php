<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Model\UserAppSettings;
use App\Settings\GeneralSetting;
use App\Themes\ThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserThemeController extends Controller
{
    public function update(
        Request $request,
        GeneralSetting $generalSetting,
        ThemeRegistry $registry
    ): RedirectResponse {
        abort_unless($generalSetting->allow_user_theme ?? true, 403, 'Theme-Auswahl nicht erlaubt.');

        $request->validate([
            'theme' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($registry) {
                    if ($value !== null && $value !== '' && ! $registry->exists($value)) {
                        $fail('Ungültiges Theme.');
                    }
                },
            ],
        ]);

        $theme = (string) $request->input('theme', '');

        $userSettings = UserAppSettings::firstOrNew(['user_id' => Auth::id()]);
        $settings = $userSettings->settings ?? [];

        if ($theme === '') {
            unset($settings['theme']);
        } else {
            $settings['theme'] = $theme;
        }

        $userSettings->settings = $settings;
        $userSettings->save();

        return back()->with([
            'type'    => 'success',
            'Meldung' => 'Design gespeichert.',
        ]);
    }
}



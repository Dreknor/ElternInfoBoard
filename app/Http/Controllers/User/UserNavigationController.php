<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Model\UserAppSettings;
use App\Services\NavigationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserNavigationController extends Controller
{
    public function update(Request $request, NavigationService $navigation): RedirectResponse
    {
        abort_unless($navigation->allowUserStyle(), 403, 'Auswahl der Menü-Darstellung nicht erlaubt.');

        $request->validate([
            'nav_style' => ['nullable', 'string', Rule::in(array_keys($navigation->styles()))],
        ]);

        $style = (string) $request->input('nav_style', '');

        $userSettings = UserAppSettings::firstOrNew(['user_id' => Auth::id()]);
        $settings = $userSettings->settings ?? [];

        if ($style === '') {
            unset($settings['nav_style']);
        } else {
            $settings['nav_style'] = $style;
        }

        $userSettings->settings = $settings;
        $userSettings->save();

        return redirect(url('einstellungen').'#design')->with([
            'type' => 'success',
            'Meldung' => 'Menü-Darstellung gespeichert.',
        ]);
    }
}

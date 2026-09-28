<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTokenRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Model\Changelog;
use App\Model\UserAppSettings;
use App\Settings\GeneralSetting;
use App\Themes\ThemeRegistry;
use App\Model\Child;
use App\Model\GuardianLinkReport;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Hash;

class BenutzerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }

    /**
     * Eltern melden eine falsche Kind-Beziehung; die Verwaltung klärt (E3/E6).
     */
    public function reportGuardianLink(Request $request, Child $child): RedirectResponse
    {
        $isLinked = $child->parents()->where('users.id', $request->user()->id)->exists();

        if (! $isLinked) {
            return redirect()->back()->with(['type' => 'danger', 'Meldung' => 'Diese Verbindung besteht nicht.']);
        }

        GuardianLinkReport::firstOrCreate(
            ['child_id' => $child->id, 'user_id' => $request->user()->id, 'resolved_at' => null],
            ['reported_by' => $request->user()->id, 'note' => $request->input('note')],
        );

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Danke, die Schule wurde informiert und prüft die Verbindung.',
        ]);
    }

    /**
     * @return Application|View
     */
    public function show(Request $request)
    {
        if ($request->session()->get('changelog')) {
            $changelog = Changelog::where('changeSettings', 1)->orderByDesc('created_at')->first();
        } else {
            $changelog = null;
        }

        return view('user.settings', [
            'user' => auth()->user(),
            'changelog' => $changelog,
            'themes' => app(ThemeRegistry::class)->all(),
            'userTheme' => data_get(
                UserAppSettings::where('user_id', auth()->id())->first()?->settings,
                'theme',
                ''
            ),
            'generalSettings' => app(GeneralSetting::class),
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = auth()->user();
        $user->update(
            $request->safe()->only([
                'name',
                'email',
                'benachrichtigung',
                'sendCopy',
                'track_login',
                'publicMail',
                'publicPhone',
                'phone',
                'calendar_prefix',
                'releaseCalendar',
                'messenger_discoverable',
            ])
        );

        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($request->password),
                'changePassword' => false,
            ]);
        }

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Gespeichert.',
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function createToken(CreateTokenRequest $request)
    {
        $user = auth()->user();
        $token = $user->createToken($request->name);

        return redirect(url('einstellungen'))->with([
            'token' => $token->plainTextToken,
            'type' => 'success',
            'Meldung' => 'Token erstellt.',
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function deleteToken($token)
    {
        $user = auth()->user();
        $user->tokens()->where('id', $token)->delete();

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Token gelöscht.',
        ]);
    }
}

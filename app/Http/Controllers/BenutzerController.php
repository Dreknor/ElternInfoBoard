<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\CreateTokenRequest;
use App\Http\Requests\editUserRequest;
use App\Model\Changelog;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function update(editUserRequest $request)
    {
        $user = auth()->user();
        $user->update(
            $request->only([
                'name',
                'email',
                'benachrichtigung',
                'sendCopy',
                'track_login',
                'publicMail',
                'publicPhone',
                'calendar_prefix',
                'releaseCalendar',
            ])
        );

        if ($request->input('password') != '' && $request->password == $request->password_confirmation) {
            $user->update([
                'password' => Hash::make($request->password),
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

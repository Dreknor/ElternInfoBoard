<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTokenRequest;
use App\Http\Requests\editUserRequest;
use App\Model\Changelog;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 *
 */
class BenutzerController extends Controller
{


    /**
     *
     */
    public function __construct()
    {
        $this->middleware('auth');

    }

    /**
     * @param Request $request
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
     * @param editUserRequest $request
     * @return RedirectResponse
     */
    public function update(editUserRequest $request)
    {
        $user = auth()->user();
        $user->update($request->validated());

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Gespeichert.',
        ]);
    }

    /**
     * @param CreateTokenRequest $request
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
     * @param
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

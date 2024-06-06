<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Model\User;
use App\Notifications\SendPasswordLessLinkNotification;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    {

        if ($request->input('submit') == 'password-less') {
            $user = $this->loginViaPasswordLessLink($request);

            if (!$user or !$user->can('allow password-less-login')) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Benutzer existiert nicht oder hat keine Berechtigung für den Passwortlosen Login.'])
                    ->withInput();
            }

            return redirect()->route('login')
                ->with([
                    'type' => 'success',
                    'Meldung' => 'Login-Link wurde an die angegebene E-Mail-Adresse gesendet.'
                ]);
        }

        $this->validateLogin($request);

        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }
            return $this->sendLoginResponse($request);
        }

        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function loginViaPasswordLessLink(Request $request)
    {
        $user = User::where('email', $request->input('email'))->first();

        if ($user) {
            $user->notify(new SendPasswordLessLinkNotification());
        }

        return $user;
    }
}

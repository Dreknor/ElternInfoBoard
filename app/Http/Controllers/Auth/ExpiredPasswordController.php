<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordExpiredRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ExpiredPasswordController extends Controller
{
    public function expired()
    {
        if (Auth::check() and Auth::user()->changePassword == 1){
            return view('auth.passwords.expired');
        } else {
            return redirect(url('/'));
        }

    }

    public function postExpired(PasswordExpiredRequest $request)
    {
        // Checking current password
        if (! Hash::check($request->current_password, auth()->user()->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Current password is not correct']);
        }
        auth()->user()->update([
            'password' => bcrypt($request->password),
            'changePassword' => 0,
        ]);

        return redirect(url('home'))->with(['status' => 'Password changed successfully']);
    }
}

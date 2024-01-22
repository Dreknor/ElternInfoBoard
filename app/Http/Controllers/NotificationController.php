<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function read(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        if (!auth()->user()->notifications()->where('id', $request->id)->exists()) {
            return response()->json(['success' => false]);
        }

        $notification = auth()->user()->notifications()->where('id', $request->id)->first();
        $notification->read = true;
        $notification->save();

        return response()->json(['success' => true]);
    }
}

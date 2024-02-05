<?php

namespace App\Http\Controllers;

use App\Model\Notification;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

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

    public function readAll()
    {
        auth()->user()->notifications()->update(['read' => true]);
        return redirect()->back();
    }

    public function readByType(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
        ]);

        auth()->user()->notifications()->where('type', $request->type)->update(['read' => true]);

    }



    public function clean_up()
    {
        Notification::query()->where('created_at', '<', now()->subDays(10))->delete();
        Notification::query()->where('created_at', '<', now()->subDays(3))->where('read', 1)->delete();
        $admins = Role::query()->where('name', 'Administrator')->first()->users()->get();

        foreach ($admins as $admin) {
            $notification = new Notification([
                'user_id' => $admin->id,
                'title' => 'Clean up',
                'message' => 'Clean up notifications',
                'important' => false,
                'type' => 'Admin',
            ]);
            $notification->save();
        }

    }
}

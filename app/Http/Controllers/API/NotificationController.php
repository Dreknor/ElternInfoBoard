<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $notification = new Notification([
            'user_id' => $user->id,
            'title' => 'Abruf',
            'message' => 'Benachrichtigungen abgerufen um ' . Carbon::now()->format('d.m.Y H:i:s') . ' Uhr',
            'url' => '',
            'type' => 'info',
            'icon' => 'info',
            'read' => false,
            'important' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $notification->save();

        $notifications = $user->notifications()->where('read',0)->orderBy('created_at', 'desc')->get();

        return response()->json([
            'notifications' => $notifications,
        ], 200);
    }

    public function read(Request $request)
    {
        $user = $request->user();


        $request->validate([
            'id' => 'required|integer'
        ]);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->notifications()->where('id', $request->id)->update(['read' => 1]);

        return response()->json([
            'message' => 'success',
        ], 200);
    }
}

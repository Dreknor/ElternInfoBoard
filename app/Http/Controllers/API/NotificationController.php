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


    public function readAll(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->notifications()->update(['read' => 1]);

        return response()->json([
            'message' => 'success',
        ], 200);
    }


    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        Log::info('User ' . $user->id . ' requested notifications');


        $notifications = $user->notifications()->where('read',0)->orderBy('created_at', 'desc')->get();

        return response()->json([
            'notifications' => $notifications,
        ], 200);
    }


    public function readAllByType (Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'type' => 'required|string'
        ]);

        $user->notifications()->where('type', $request->type)->where('user_id', $user->id)->update(['read' => 1]);

        return response()->json([
            'message' => 'success',
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


        $notification = $user->notifications()->where('id', $request->id)->first();

        $user->notifications()->where('type', $notification->type)->where('user_id', $user->id)->update(['read' => 1]);


        return response()->json([
            'message' => 'success',
        ], 200);
    }
}

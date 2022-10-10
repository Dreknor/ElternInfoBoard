<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePushRequest;
use App\Model\User;
use App\Notifications\PushNews;
use Illuminate\Support\Facades\Notification;

class PushController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Store the PushSubscription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StorePushRequest $request)
    {
        $endpoint = $request->endpoint;
        $token = $request->keys['auth'];
        $key = $request->keys['p256dh'];
        $user = $request->user();
        $user->updatePushSubscription($endpoint, $key, $token);

        return response()->json(['success' => true], 200);
    }

    /*
        public function push(){
            Notification::send(User::all(),new PushNews());
            return redirect()->back();
        }
    */
}

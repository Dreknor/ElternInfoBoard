<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePushRequest;
use App\Model\Notification;
use App\Notifications\Push;
use Illuminate\Http\JsonResponse;

class PushController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }




    /**
     * Store the PushSubscription.
     *
     * @param StorePushRequest $request
     * @return JsonResponse
     */
    public function store(StorePushRequest $request)
    {
        $endpoint = $request->endpoint;
        $token = $request->keys['auth'];
        $key = $request->keys['p256dh'];
        $user = $request->user();
        //$user->updatePushSubscription($endpoint, $key, $token, json_encode(get_browser()));
        $user->updatePushSubscription($endpoint, $key, $token, json_encode());

        return response()->json(['success' => true]);
    }

}

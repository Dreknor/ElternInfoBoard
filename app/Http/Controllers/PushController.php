<?php

namespace App\Http\Controllers;

use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
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
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'endpoint' => 'required',
            'keys.auth' => 'required',
            'keys.p256dh' => 'required',
        ]);

        $endpoint = $request->endpoint;
        $token = $request->keys['auth'];
        $key = $request->keys['p256dh'];
        $user = auth()->user();
        $user->updatePushSubscription($endpoint, $key, $token);

        return response()->json(['success' => true]);
    }

    public function push(User $user)
    {

        if (auth()->user()->can('testing')) {

            Notification::send($user, new Push('test', 'test'));
            return redirect()->back()->with([
                'Meldung' => 'Push wurde erfolgreich gesendet',
                'type' => 'success',

            ]);
        }
        return redirect()->back()->with([
            'Meldung' => 'Keine Berechtigung',
            'type' => 'error'
        ]);

    }

}

<?php

namespace App\Http\Controllers;

use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Push;

use Illuminate\Http\JsonResponse;
use Minishlink\WebPush\WebPush;

class PushController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Testen der Push-Benachrichtigung
     * @return \Illuminate\Http\RedirectResponse
     *
     */
    public function testPush(){

        if(auth()->user()->can('testing')){
            Log::info('PushController:testPush: Benachrichtigung wird gesendet an ' . auth()->user()->name);
            auth()->user()->notify(new Push('Testbenachrichtigung', 'Dies ist eine Testbenachrichtigung'));
            return redirect()->back()->with([
                'Meldung' => 'Benachrichtigung wurde gesendet',
                'type' => 'success'
            ]);
        }
        return redirect()->back()->with([
            'Meldung' => 'Keine Berechtigung',
            'type' => 'danger'
        ]);

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
            Log::info('PushController:push: Benachrichtigung wird gesendet an ' . $user->name);
            Notification::send($user, new Push('Testbenachrichtigung', 'Dies ist eine Testbenachrichtigung'));
            return redirect()->back()->with([
                'Meldung' => 'Benachrichtigung wurde gesendet',
                'type' => 'success'
            ]);
        }
        return redirect()->back()->with([
            'Meldung' => 'Keine Berechtigung',
            'type' => 'danger'
        ]);

    }

}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\UserRueckmeldung as UserRueckmeldungMail;
use App\Model\AbfrageAntworten;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use App\Model\UserRueckmeldungen;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserRueckmeldungenController extends Controller
{


    public function store(Request $request)
    {
        $post = Post::query()->find($request->post_id);
        $user = $request->user();

        if ($post->rueckmeldung->multiple != 1) {
            $userRueckmeldung = UserRueckmeldungen::query()
                ->where('post_id', $request->post_id)
                ->where('users_id', $user->id)
                ->first();

            if ($userRueckmeldung) {
                return response()->json([
                    'error' => 'Rückmeldung bereits abgegeben',
                    'userRueckmeldung' => $userRueckmeldung
                    ], 409);
            }
        }

        $userRueckmeldung = new UserRueckmeldungen(
            [
                'post_id' => $request->post_id,
                'users_id' => $user->id,
                'text' => $request->text,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $userRueckmeldung->save();

        $rueckmeldung = [
            'email' => $user->email,
            'name' => $user->name,
            'text' => $request->text,
            'subject' => "Rückmeldung zu ".$post->header,
        ];

        $empfaenger = $post->rueckmeldung->empfaenger;

        if ($user->sendCopy == 1) {
            Mail::to($empfaenger)
                ->cc($user)
                ->queue(new UserRueckmeldungMail((array)$rueckmeldung));
        } else {
            Mail::to($empfaenger)
                ->queue(new UserRueckmeldungMail((array)$rueckmeldung));
        }

        return response()->json([
            'success' => 'Rückmeldung abgegeben',
            'userRueckmeldung' => $userRueckmeldung
        ], 200);
    }



}

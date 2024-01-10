<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\KontaktRequest;
use App\Mail\SendFeedback;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


/**
 *
 */
class ContactController extends Controller
{

     public function __construct()
     {
            $this->middleware('auth:sanctum');
     }

     public function index()
     {
            $mitarbeiter = User::whereHas('roles', function ($q) {
                $q->where('name', 'Mitarbeiter');
            })->orWhereHas('permissions', function ($q) {
                $q->where('name', 'show in contact form');
            })->orderBy('name')->get(['id', 'name']);

            $mitarbeiter->prepend(['id' => 0, 'name' => 'Sekretariat']);

            return response()->json(
                ['data' => $mitarbeiter]
            );
     }

     public function send(KontaktRequest $request)
     {

        if ($request->input('mitarbeiter') != 0) {
            $email = User::query()->where('id', $request->input('mitarbeiter') )->value('email');
        } else {
            $email = config('mail.from.address');
        }

         try {


             Mail::to($email)->send(new SendFeedback($request->input('text'),$request->input('betreff')));

             return response()->json(['success' => 'Mail sent'], 200);
         } catch (\Exception $e) {

             return response()->json(['error' => 'Mail not sent'], 500);
         }

     }
}

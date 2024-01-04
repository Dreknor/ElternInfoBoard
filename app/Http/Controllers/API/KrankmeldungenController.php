<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\krankmeldung;
use App\Model\ActiveDisease;
use App\Model\Disease;
use App\Model\krankmeldungen;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use function Aws\map;

class KrankmeldungenController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function getDiseses(Request $request)
    {
        $diseases = Disease::query()->get(['id', 'name']);

        return response()->json($diseases, 200);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'kommentar' => 'required',
            'start' => 'required',
            'ende' => 'required',
            'disease_id' => 'nullable',
        ]);

        $krankmeldung = new krankmeldungen(
            [
                'name' => $request->name,
                'kommentar' => $request->kommentar,
                'start' => Carbon::createFromFormat('d.m.Y', $request->start),
                'ende' => Carbon::createFromFormat('d.m.Y', $request->ende),
                'users_id' => $request->user()->id,
            ]
        );
        $krankmeldung->save();


        if ($request->disease_id != null &&  $request->disease_id != 0) {
            $disease = Disease::find($request->disease_id);
            ActiveDisease::insert([
                'user_id' => auth()->id(),
                'disease_id' => $request->disease_id,
                'start' => $krankmeldung->start,
                'end' => $krankmeldung->start->addDays($disease->aushang_dauer),
                'active' => false,
            ]);

            Cache::forget('active_diseases');
        }

        Mail::to(config('mail.from.address'))
            ->cc($request->user()->email)
            ->queue(new krankmeldung($request->user()->email, $request->user()->name, $request->name, $request->start, $request->ende, $request->kommentar, $disease->name ?? null));

        return response()->json('Krankmeldung gesendet.',200);
    }

    public function getActiveDisease(Request $request)
    {
        $activeDisease = ActiveDisease::query()
            ->where('active', true)
            ->with('disease')
            ->get();

        if (count($activeDisease) >0) {
                $result = [];

               foreach ($activeDisease as $key => $disease) {
                   $result[] = [
                          'id' => $disease->id,
                       'name' => $disease->disease->name,
                       'start' => $disease->start->format('Y-m-d'),
                   ] ;
               }


            return response()->json(
               ['data' => $result]
            , 200);
        } else {
            return response()->json(null, 200);
        }
    }


}

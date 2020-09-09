<?php

namespace App\Http\Controllers;

use App\Model\Group;
use App\Model\Reinigung;
use App\Model\User;
use App\Support\Collection;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\View\View;

class ReinigungController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        $user = auth()->user();
        $datum = Carbon::now()->startOfWeek()->startOfDay();
        $ende = Carbon::createFromFormat('d.m', '30.8');


        if ($datum->month > 6){
            $ende->addYear();
        }

        if (!$user->can('edit reinigung')){
            $user->load('groups');
            $Bereiche = $user->groups->pluck('bereich')->unique();
        } else {
            $Bereiche = Group::query()->whereNotNull('bereich')->pluck('bereich')->unique();
        }

        $Reinigung = [];

        foreach ($Bereiche as $Bereich){
            $Reinigung[$Bereich] = Reinigung::query()
                ->where('bereich', $Bereich)
                ->whereDate('datum', '>=',$datum)
                ->orderBy('datum')
                ->get();
        }

        return view('reinigung.show',[
            "Bereiche"  => $Bereiche,
            'Familien' => $Reinigung,
            "datum"     => $datum,
            "user"      => $user,
            "ende"      => $ende,
            "users"     => User::all(),
            "aufgaben"  => Reinigung::query()->pluck('aufgabe')->unique()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return RedirectResponse
     */
    public function create($Bereich, $Datum)
    {

        if (!auth()->user()->can('edit reinigung')) {
            return redirect()->back()->with([
                'type'  => "danger",
                'Meldung'   => "Berechtigung fehlt"
            ]);
        }

        $user = auth()->user();
        $datum = Carbon::createFromFormat('Ymd', $Datum)->startOfWeek()->startOfDay();
        $ende = $datum->endOfWeek()->endOfDay();

       $newusers = User::whereHas('groups', function ($query) use ($Bereich) {
           $query->where('bereich', '=', $Bereich);
       })->get();

       $newusers = $newusers->sortBy('familie_name');

        $Reinigung = [];

            $Reinigung = Reinigung::query()
                ->where('bereich', $Bereich)
                ->whereDate('datum', '>=',$datum->copy()->subWeek())
                ->orderBy('datum')
                ->get();



            $Aufgaben = [
                "Garderobe links kehren und wischenn Spinnenweben entfernen",
                "Treppe, Garderobenvorraum, Gang zum Hof kehren und wischen",
                "Garderobe rechts kehren und wischen Spinnenweben entfernen",
                "Schrankfächer in der Garderobe auswischen 2 Teppiche im Eingangsbereich saugen",
                "Fußboden in der Garderobe und die Treppe im Keller kehren und wischen",
                "alle Fensterbretter in allen Klassenzimmern abwischen 2 Teppiche im Eingangsbereich saugen",
                'Materialregale im Klassenraum "Feuer" abstauben, und  2 Teppiche im Eingangsbereich saugen',
                'Materialregale im Klassenraum "Osten" abstauben, und  2 Teppiche im Eingangsbereich saugen',
                'Materialregale im Musikraum abstauben, und  2 Teppiche im Eingangsbereich saugen',
                'Materialregale im Kunstraum abstauben, und  2 Teppiche im Eingangsbereich saugen',
                'Materialregale im Klassenraum "Norden" abstauben, und  2 Teppiche im Eingangsbereich saugen'
            ];



        return view('reinigung.edit',[
            "Bereich"  => $Bereich,
            'Familien' => $Reinigung,
            "datum"     => $datum,
            "ende"      => $ende,
            "users"     => $newusers,
            "aufgaben"  => $Aufgaben
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store($Bereich, Request $request)
    {
        $Datum = Carbon::createFromFormat('d.m.Y',$request->input('datum'));

        $Reinigung = Reinigung::where('bereich', $Bereich)->whereDate('datum', $Datum)->get();
        if (count($Reinigung)>1){
            $Reinigung_Fam1 = $Reinigung->first();
            $Reinigung_Fam2 = $Reinigung->last();

            if (isset($request->usersID_first) and isset($request->aufgabe_first)){
                $Reinigung_Fam1->users_id  = $request->input('usersID_first');
                $Reinigung_Fam1->aufgabe  = $request->input('aufgabe_first');
                $Reinigung_Fam1->save();

            } else {
                $Reinigung_Fam1->delete();
            }

            if (isset($request->usersID_last) and isset($request->aufgabe_last)){
                $Reinigung_Fam2->users_id  = $request->input('usersID_last');
                $Reinigung_Fam2->aufgabe  = $request->input('aufgabe_last');

                $Reinigung_Fam2->save();

            } else {
                $Reinigung_Fam2->delete();
            }



        } elseif (count($Reinigung)==1){

            $Reinigung_Fam1 = $Reinigung->first();

            if (isset($request->usersID_first) and isset($request->aufgabe_first)){
                $Reinigung_Fam1->users_id  = $request->input('usersID_first');
                $Reinigung_Fam1->aufgabe  = $request->input('aufgabe_first');
                $Reinigung_Fam1->save();

            } else {
                $Reinigung_Fam1->delete();
            }

            if (isset($request->usersID_last) and isset($request->aufgabe_last)){

                $Reinigung_Fam2 = new Reinigung();
                $Reinigung_Fam2->users_id  = $request->input('usersID_last');
                $Reinigung_Fam2->aufgabe  = $request->input('aufgabe_last');
                $Reinigung_Fam2->bereich  = $Bereich;
                $Reinigung_Fam2->datum  = $Datum;
                $Reinigung_Fam2->save();
            }








        } else {

            if (isset($request->usersID_first) and isset($request->aufgabe_first)){
                $Reinigung_Fam1 = new Reinigung();
                $Reinigung_Fam1->users_id  = $request->input('usersID_first');
                $Reinigung_Fam1->aufgabe  = $request->input('aufgabe_first');
                $Reinigung_Fam1->bereich  = $Bereich;
                $Reinigung_Fam1->datum  = $Datum;
                $Reinigung_Fam1->save();

            }

            if (isset($request->usersID_last) and isset($request->aufgabe_last)){

                $Reinigung_Fam2 = new Reinigung();
                $Reinigung_Fam2->users_id  = $request->input('usersID_last');
                $Reinigung_Fam2->aufgabe  = $request->input('aufgabe_last');
                $Reinigung_Fam2->bereich  = $Bereich;
                $Reinigung_Fam2->datum  = $Datum;
                $Reinigung_Fam2->save();
            }



        }

        return redirect(url('reinigung'))->with([
            'type'  => "success",
            'Meldung'   => "Plan aktualisiert"
        ]);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Model\Reinigung  $reinigung
     * @return \Illuminate\Http\Response
     */
    public function show(Reinigung $reinigung)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\Reinigung  $reinigung
     * @return \Illuminate\Http\Response
     */
    public function edit(Reinigung $reinigung)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Reinigung  $reinigung
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Reinigung $reinigung)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\Reinigung  $reinigung
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reinigung $reinigung)
    {
        //
    }
}

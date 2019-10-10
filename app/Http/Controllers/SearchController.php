<?php

namespace App\Http\Controllers;

use App\Http\Requests\searchRequest;
use App\Model\Groups;
use App\Model\Posts;
use App\Support\Collection;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','password_expired']);
    }

    public function search( searchRequest $request){


        $months = new Collection([
            "1" => "Januar",
            "2" => "Februar",
            "3" => "März",
            "4" => "April",
            "5" => "Mai",
            "6" => "Juni",
            "7" => "Juli",
            "8" => "August",
            "9" => "September",
            "10" => "Oktober",
            "11" => "November",
            "12" => "Dezember",
        ]);

        if (!auth()->user()->can('create posts')){
            if ($months->search($request->input('suche'))){
                $Nachrichten = auth()->user()->posts()
                    ->whereMonth('posts.updated_at', $months->search($request->input('suche')))
                    ->orWhereLike(['header','news'], $request->input('suche'))
                    ->with('rueckmeldung', 'autor')
                    ->get();
            } else {
                $Nachrichten = auth()->user()->posts()
                    ->whereLike(['header','news'], $request->input('suche'))
                    ->with('rueckmeldung', 'autor')
                    ->get();
            }

        } else {
            if ($months->search($request->input('suche'))){
                $Nachrichten = Posts::whereMonth('posts.updated_at', $months->search($request->input('suche')))
                    ->orWhereLike(['header', 'news'], $request->input('suche'))
                    ->with('rueckmeldung', 'autor')
                    ->get();

            } else {
                $Nachrichten = Posts::whereLike(['header', 'news'], $request->input('suche'))
                    ->with('rueckmeldung', 'autor')
                    ->get();
            }

        }


        $Nachrichten = $Nachrichten->unique()->sortByDesc('updated_at')->all();


        return view('search.result', [
            "nachrichten"   => $Nachrichten,
            "archiv"    => null,
            "user"      => auth()->user(),
            "gruppen"   => Groups::all(),
            "Suche"     => $request->input('suche')
        ]);

    }
}

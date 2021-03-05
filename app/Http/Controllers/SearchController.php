<?php

namespace App\Http\Controllers;

use App\Http\Requests\searchRequest;
use App\Model\Group;
use App\Model\Post;
use App\Support\Collection;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'password_expired']);
    }

    public function search(searchRequest $request)
    {
        $months = new Collection([
            '1' => 'Januar',
            '2' => 'Februar',
            '3' => 'März',
            '4' => 'April',
            '5' => 'Mai',
            '6' => 'Juni',
            '7' => 'Juli',
            '8' => 'August',
            '9' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Dezember',
        ]);

        if (! $request->user()->can('create posts')) {
            if ($months->search($request->input('suche'))) {
                $Nachrichten = $request->user()->posts()
                    ->whereMonth('posts.updated_at', $months->search($request->input('suche')))
                    ->orWhereLike(['header', 'news'], $request->input('suche'))
                    ->with('rueckmeldung', 'autor')
                    ->get();
            } else {
                $Nachrichten = $request->user()->posts()
                    ->whereLike(['header', 'news'], $request->input('suche'))
                    ->with('rueckmeldung', 'autor')
                    ->get();
            }
        } else {
            if ($months->search($request->input('suche'))) {
                $Nachrichten = Post::whereMonth('posts.updated_at', $months->search($request->input('suche')))
                    ->orWhereLike(['header', 'news'], $request->input('suche'))
                    ->with('rueckmeldung', 'autor')
                    ->get();
            } else {
                $Nachrichten = Post::whereLike(['header', 'news'], $request->input('suche'))
                    ->with('rueckmeldung', 'autor')
                    ->get();
            }
        }

        $Nachrichten = $Nachrichten->unique()->sortByDesc('updated_at')->all();

        return view('search.result', [
            'nachrichten'   => $Nachrichten,
            'archiv'    => null,
            'user'      => $request->user(),
            'gruppen'   => Group::all(),
            'Suche'     => $request->input('suche'),
        ]);
    }
}

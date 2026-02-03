<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\searchRequest;
use App\Model\Group;
use App\Model\Post;
use App\Support\Collection;
use Illuminate\View\View;

class SearchController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            ['auth', 'password_expired'],
        ];
    }

    /**
     * @return View
     */
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

        $searchString = $request->input('suche');
        $sites = auth()->user()->sites()
            ->where('sites.name', 'like', '%'.$searchString.'%')
            ->with(['blocks' => function ($query) use ($searchString) {
                $query->when($searchString, function ($query, $searchString) {
                    $query
                        ->where('site_blocks.title', 'like', '%'.$searchString.'%')
                        ->with(['blocks' => function ($query) use ($searchString) {
                            $query->when($searchString, function ($query, $searchString) {
                                $query->orWhere('sites_blocks_text.content', 'like', '%'.$searchString.'%');
                            });
                        }]);
                });
            }])->get();

        return view('search.result', [
            'nachrichten' => $Nachrichten,
            'sites' => $sites->unique()->sortByDesc('name')->all(),
            'archiv' => null,
            'user' => $request->user(),
            'gruppen' => Group::all(),
            'Suche' => $request->input('suche'),
        ]);
    }
}

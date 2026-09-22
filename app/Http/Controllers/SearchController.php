<?php

namespace App\Http\Controllers;

use App\Http\Requests\searchRequest;
use App\Model\Group;
use App\Model\Post;
use App\Model\SearchLog;
use App\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;
use Throwable;

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
        $searchTerm = $request->string('suche')->toString();
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
            if ($months->search($searchTerm)) {
                $Nachrichten = $request->user()->posts()
                    ->whereMonth('posts.updated_at', $months->search($searchTerm))
                    ->orWhereLike(['header', 'news'], $searchTerm)
                    ->with('rueckmeldung', 'autor')
                    ->get();
            } else {
                $Nachrichten = $request->user()->posts()
                    ->whereLike(['header', 'news'], $searchTerm)
                    ->with('rueckmeldung', 'autor')
                    ->get();
            }
        } else {
            if ($months->search($searchTerm)) {
                $Nachrichten = Post::whereMonth('posts.updated_at', $months->search($searchTerm))
                    ->orWhereLike(['header', 'news'], $searchTerm)
                    ->with('rueckmeldung', 'autor')
                    ->get();
            } else {
                $Nachrichten = Post::whereLike(['header', 'news'], $searchTerm)
                    ->with('rueckmeldung', 'autor')
                    ->get();
            }
        }

        $Nachrichten = $Nachrichten->unique()->sortByDesc('updated_at')->values();

        $searchString = $searchTerm;
        $sites = auth()->user()->sites()
            ->where('sites.name', 'like', '%'.$searchString.'%')
            ->with(['blocks' => function ($query) use ($searchString) {
                $query->when($searchString, function ($query, $searchString) {
                    $query
                        ->where('site_blocks.title', 'like', '%'.$searchString.'%')
                        ->with(['block' => function ($query) use ($searchString) {
                            $query->when($searchString, function ($query, $searchString) {
                                $query->orWhere('sites_blocks_text.content', 'like', '%'.$searchString.'%');
                            });
                        }]);
                });
            }])->get();

        $sites = $sites->unique()->sortByDesc('name')->values();

        $this->recordSearch($request->user()->id, $searchTerm, $Nachrichten->count(), $sites->count());

        return view('search.result', [
            'nachrichten' => $Nachrichten,
            'sites' => $sites,
            'archiv' => null,
            'user' => $request->user(),
            'gruppen' => Group::active()->get(),
            'Suche' => $searchTerm,
        ]);
    }

    /**
     * Protokolliert eine ausgeführte Suche eigenständig (search_logs), damit
     * die Statistik nicht die allgemeinen System-Logs belastet und getrennt
     * bereinigt werden kann. Fehler beim Protokollieren dürfen die Suche
     * selbst nicht beeinträchtigen.
     */
    protected function recordSearch(int $userId, string $searchTerm, int $nachrichtenCount, int $seitenCount): void
    {
        try {
            SearchLog::create([
                'user_id' => $userId,
                'search_term' => $searchTerm,
                'nachrichten_count' => $nachrichtenCount,
                'seiten_count' => $seitenCount,
                'results_count' => $nachrichtenCount + $seitenCount,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Suche konnte nicht protokolliert werden.', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
        }
    }
}

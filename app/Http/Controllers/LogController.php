<?php

namespace App\Http\Controllers;

use App\Model\SearchLog;
use Carbon\Carbon;
use danielme85\LaravelLogToDB\LogToDB;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /**
     * Vordefinierte Kategorien für den Schnellfilter. Die Zuordnung erfolgt
     * über Schlüsselwörter in der Log-Nachricht (Groß-/Kleinschreibung egal).
     */
    public const CATEGORIES = [
        'login' => [
            'label' => 'Login & Anmeldung',
            'icon' => 'fa-sign-in-alt',
            'keywords' => ['login', 'logout', 'keycloak', 'impersonation', 'anmeldung', 'passwort', 'password'],
        ],
        'mail' => [
            'label' => 'E-Mail',
            'icon' => 'fa-envelope',
            'keywords' => ['mail', 'zugangsdaten', 'kontaktformular'],
        ],
        'push' => [
            'label' => 'Push & Benachrichtigungen',
            'icon' => 'fa-bell',
            'keywords' => ['push', 'benachrichtigung', 'notification', 'erinnerung', 'reminder'],
        ],
        'nachrichten' => [
            'label' => 'Nachrichten & Rückmeldungen',
            'icon' => 'fa-newspaper',
            'keywords' => ['post ', 'nachricht', 'rückmeldung', 'rueckmeldung', 'lesebestätigung', 'wordpress'],
        ],
        'termine' => [
            'label' => 'Termine & Listen',
            'icon' => 'fa-calendar-alt',
            'keywords' => ['termin', 'liste'],
        ],
        'pflichtstunden' => [
            'label' => 'Pflichtstunden',
            'icon' => 'fa-clock',
            'keywords' => ['pflichtstunde'],
        ],
        'krankmeldung' => [
            'label' => 'Krankmeldungen',
            'icon' => 'fa-notes-medical',
            'keywords' => ['krankmeldung'],
        ],
        'hort' => [
            'label' => 'Hort & Schickzeiten',
            'icon' => 'fa-child',
            'keywords' => ['schickzeit', 'anwesenheit', 'abgeholt', 'abholvollmacht'],
        ],
        'import' => [
            'label' => 'Import',
            'icon' => 'fa-file-import',
            'keywords' => ['import'],
        ],
        'ucs' => [
            'label' => 'UCS & Synchronisation',
            'icon' => 'fa-sync-alt',
            'keywords' => ['ucs', '[sync'],
        ],
        'system' => [
            'label' => 'System & Wartung',
            'icon' => 'fa-cogs',
            'keywords' => ['wartungsmodus', 'update', 'ausnahme', 'exception', 'schuljahreswechsel', 'cleanup', 'setting'],
        ],
    ];

    /**
     * Display a listing of the logs with filtering
     */
    public function index(Request $request)
    {
        $query = LogToDB::model()->orderBy('created_at', 'desc');

        // Filter by level
        if ($request->filled('level')) {
            $query->where('level_name', $request->level);
        }

        // Filter by channel
        if ($request->filled('channel')) {
            $query->where('channel', 'like', '%'.$request->channel.'%');
        }

        // Filter by category (Login, E-Mail, ...)
        $category = $request->input('category');
        if (is_string($category) && isset(self::CATEGORIES[$category])) {
            $keywords = self::CATEGORIES[$category]['keywords'];
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhereRaw('LOWER(message) LIKE ?', ['%'.mb_strtolower($keyword).'%']);
                }
            });
        }

        // Filter by date range
        if ($request->filled('from') && ($from = $this->parseDate($request->input('from')))) {
            $query->where('created_at', '>=', $from->startOfDay());
        }
        if ($request->filled('to') && ($to = $this->parseDate($request->input('to')))) {
            $query->where('created_at', '<=', $to->endOfDay());
        }

        // Search in message and context (z.B. E-Mail-Adresse oder User-ID)
        if ($request->filled('search')) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', $search)
                    ->orWhere('context', 'like', $search);
            });
        }

        $logs = $query->paginate(50)->withQueryString();
        $totalLogs = LogToDB::model()->count();
        $channels = LogToDB::model()->whereNotNull('channel')->distinct()->orderBy('channel')->pluck('channel');
        $searchStats = $this->buildSearchStats();

        return view('logs.index', [
            'logs' => $logs,
            'totalLogs' => $totalLogs,
            'channels' => $channels,
            'categories' => self::CATEGORIES,
            'searchStats' => $searchStats,
        ]);
    }

    protected function parseDate($value): ?Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Delete a single log entry
     */
    public function destroy($id)
    {
        $log = LogToDB::model()->findOrFail($id);
        $log->delete();

        return redirect()->back()->with('success', 'Log-Eintrag wurde gelöscht.');
    }

    /**
     * Delete logs older than specified days
     */
    public function cleanup(Request $request)
    {
        $days = $request->input('days', 30);
        $date = Carbon::now()->subDays($days);

        $deletedCount = LogToDB::model()
            ->where('created_at', '<', $date)
            ->delete();

        return redirect()->back()->with('success', "Es wurden {$deletedCount} Log-Einträge gelöscht, die älter als {$days} Tage waren.");
    }

    /**
     * Delete search log entries older than specified days
     */
    public function cleanupSearchLogs(Request $request)
    {
        $days = $request->input('days', 90);
        $date = Carbon::now()->subDays($days);

        $deletedCount = SearchLog::where('created_at', '<', $date)->delete();

        return redirect()->back()->with('success', "Es wurden {$deletedCount} Sucheinträge gelöscht, die älter als {$days} Tage waren.");
    }

    /**
     * Baut die Suchstatistik aus den eigenständigen search_logs auf, damit
     * sie unabhängig von den allgemeinen System-Logs ausgewertet werden kann.
     */
    protected function buildSearchStats(): array
    {
        $totalSearches = SearchLog::count();

        $recentSearchLogs = SearchLog::orderByDesc('created_at')
            ->limit(10)
            ->get();

        $averageResults = round((float) SearchLog::avg('results_count'), 1);

        $topSearchTerms = SearchLog::selectRaw('search_term, COUNT(*) as searches')
            ->groupBy('search_term')
            ->orderByDesc('searches')
            ->limit(5)
            ->get();

        $resultsDistribution = [
            'ohne_treffer' => SearchLog::withoutResults()->count(),
            'mit_treffern' => SearchLog::withResults()->count(),
        ];

        return [
            'totalSearches' => $totalSearches,
            'averageResults' => $averageResults,
            'topSearchTerms' => $topSearchTerms,
            'resultsDistribution' => $resultsDistribution,
            'searchLogs' => $recentSearchLogs,
        ];
    }
}

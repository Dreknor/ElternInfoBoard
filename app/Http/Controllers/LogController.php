<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use danielme85\LaravelLogToDB\LogToDB;
use Illuminate\Http\Request;

class LogController extends Controller
{
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

        // Search in message
        if ($request->filled('search')) {
            $query->where('message', 'like', '%'.$request->search.'%');
        }

        $logs = $query->paginate(50)->withQueryString();
        $totalLogs = LogToDB::model()->count();

        return view('logs.index', [
            'logs' => $logs,
            'totalLogs' => $totalLogs,
        ]);
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
}

<?php

namespace App\Http\Controllers;

use App\Services\FamilyWeeklyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class FamilyWeeklyController extends Controller implements HasMiddleware
{
    public function __construct(
        private FamilyWeeklyService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('password_expired'),
        ];
    }

    /**
     * Wochenplan-Ansicht
     */
    public function index(Request $request)
    {
        $request->validate([
            'week' => ['nullable', 'regex:/^\d{4}-W\d{1,2}$/'],
        ]);

        $weekStart = $this->parseWeek($request->input('week'));
        $data      = $this->service->getWeeklyData(auth()->user(), $weekStart);

        $data['prev_week']    = $data['week_start']->copy()->subWeek()->format('Y-\WW');
        $data['next_week']    = $data['week_start']->copy()->addWeek()->format('Y-\WW');
        $data['current_week'] = now()->startOfWeek()->format('Y-\WW');

        return view('family.weekly', $data);
    }

    /**
     * PDF-Export des Wochenplans
     */
    public function exportPdf(Request $request)
    {
        $request->validate([
            'week'     => ['nullable', 'regex:/^\d{4}-W\d{1,2}$/'],
            'child_id' => ['nullable', 'integer', 'exists:children,id'],
        ]);

        $weekStart = $this->parseWeek($request->input('week'));
        $data      = $this->service->getWeeklyData(auth()->user(), $weekStart);

        // Optional: nur ein bestimmtes Kind
        if ($request->filled('child_id')) {
            $data['children'] = $data['children']->filter(
                fn ($c) => $c['child']->id == $request->child_id
            )->values();
        }

        $pdf = Pdf::loadView('family.weekly-pdf', $data);
        $pdf->setPaper('A4', 'landscape');

        $filename = 'Wochenplan_' . str_replace([':', ' '], ['', '_'], $data['week_label']) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * ISO-Woche-String (z.B. "2026-W15") in ein Carbon-Datum umwandeln.
     */
    private function parseWeek(?string $week): ?Carbon
    {
        if (! $week) {
            return null;
        }

        // Format: YYYY-WNN
        $parts = explode('-W', $week);
        if (count($parts) !== 2) {
            return null;
        }

        return Carbon::now()
            ->setISODate((int) $parts[0], (int) $parts[1])
            ->startOfWeek();
    }
}


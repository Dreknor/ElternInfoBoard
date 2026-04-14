<?php

namespace App\Http\Controllers\Anwesenheit;

use App\Exports\AnwesenheitsAbfrageExport;
use App\Http\Controllers\Controller;
use App\Jobs\AnwesenheitNotificationJob;
use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\ChildMandate;
use App\Model\Groups;
use App\Model\Notification;
use App\Model\User;
use App\Services\HolidayService;
use App\Notifications\AttendanceQueryNotification;
use App\Settings\CareSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class CareController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }

    /**
     * Displays the attendance overview.
     *
     * @param bool \$showAll Decides whether to show all children or only those checked in.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View Renders the attendance view.
     */
    public function index($showAll = false)
    {
        $careSettings = new CareSetting;

        if ($showAll == 1) {
            return redirect()->route('anwesenheit.index')->withCookie(cookie()->forever('showAll', true));
        } elseif ($showAll == 'off') {
            return redirect()->route('anwesenheit.index')->withCookie(cookie()->forever('showAll', false));
        }

        $groups = Groups::query()->whereIn('id', $careSettings->groups_list)->get();
        $classes = Groups::query()->whereIn('id', $careSettings->class_list)->get();

        // Wenn keine Gruppen oder Klassen konfiguriert sind, keine Kinder anzeigen
        if (empty($careSettings->groups_list) || empty($careSettings->class_list)) {
            $childs = collect();
        } elseif ($careSettings->hide_childs_when_absent == true && ! request()->cookie('showAll')) {
            $childs = Child::query()
                ->whereIn('group_id', $careSettings->groups_list)
                ->whereIn('class_id', $careSettings->class_list)
                ->whereHas('checkIns', function ($query) {
                    $query
                        ->checkedIn()
                        ->whereDate('date', now()->toDateString());
                })
                ->with([
                    'mandates',
                    'checkIns' => function ($query) {
                        $query->whereDate('date', today());
                    },
                    'schickzeiten' => function ($query) {
                        $query->where('specific_date', today())
                            ->orderBy('specific_date', 'desc');
                    },
                    'krankmeldungen' => function ($query) {
                        $query->whereDate('start', '<=', today())
                            ->whereDate('ende', '>=', today());
                    },
                    'notice' => function ($query) {
                        $query->whereDate('date', today());
                    },
                    'arbeitsgemeinschaften' => function ($query) {
                        $query->where('end_date', '>', now())
                            ->where('weekday', now()->dayOfWeek)
                            ->where(function ($q) {
                                $q->whereDate('start_date', '<=', today())
                                    ->orWhereNull('start_date');
                            })
                            ->where(function ($q) {
                                $q->whereDate('end_date', '>=', today())
                                    ->orWhereNull('end_date');
                            });
                    }
                ])
                ->get();

        } else {
            $childs = Child::query()
                ->whereIn('group_id', $careSettings->groups_list)
                ->whereIn('class_id', $careSettings->class_list)
                ->with([
                    'mandates',
                    'checkIns' => function ($query) {
                        $query->whereDate('date', today());
                    },
                    'schickzeiten' => function ($query) {
                        $query->where('specific_date', today())
                            ->orderBy('specific_date', 'desc');
                    },
                    'krankmeldungen' => function ($query) {
                        $query->whereDate('start', '<=', today())
                            ->whereDate('ende', '>=', today());
                    },
                    'notice' => function ($query) {
                        $query->whereDate('date', today());
                    },
                    'arbeitsgemeinschaften' => function ($query) {
                        $query->where('end_date', '>', now())
                            ->where('weekday', now()->dayOfWeek)
                            ->where(function ($q) {
                                $q->whereDate('start_date', '<=', today())
                                    ->orWhereNull('start_date');
                            })
                            ->where(function ($q) {
                                $q->whereDate('end_date', '>=', today())
                                    ->orWhereNull('end_date');
                            });
                    }
                ])
                ->get();
        }


        $isFerientag = (new HolidayService())->isTodayHoliday();

        return view('anwesenheit.index', [
            'children' => $childs,
            'groups' => $groups,
            'classes' => $classes,
            'careSettings' => $careSettings,
            'isFerientag' => $isFerientag,
        ]);
    }

    /**
     * Marks a child as checked out and clears its cache entry.
     *
     * @param Child \$child Child model whose attendance is updated.
     * @return \Illuminate\Http\JsonResponse JSON response confirming success.
     */
    public function abmelden(Child $child)
    {
        $child->checkIns()
            ->where('checked_in', true)
            ->where('checked_out', false)
            ->whereDate('date', now()->toDateString())
            ->update([
                'checked_out' => true,
            ]);

        $careSettings = new CareSetting;

        if ($careSettings->end_time != null and
            Carbon::parse($careSettings->end_time)->lt(now()) and
            $careSettings->info_to != null
        ) {
            try {
                $user = User::find($careSettings->info_to);
                if ($user) {
                    $notification = new Notification([
                        'user_id' => $user->id,
                        'type' => 'info',
                        'title' => 'Verspätete Abmeldung',
                        'message' => 'Das Kind '.$child->first_name.' '.$child->last_name.' wurde nicht rechtzeitig abgemeldet.',
                    ]);
                    $notification->save();

                    Log::info('Kind verspätet abgeholt', [
                        'child_id' => $child->id,
                        'child_name' => $child->first_name.' '.$child->last_name,
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error sending notification: '.$e->getMessage());
            }

        }

        $parent = $child->parents()->first();

        if ($child->notification) {
            dispatch(new AnwesenheitNotificationJob($parent, $child->first_name, 'checkOut'));

            if ($parent->sorgorgeberechtigter2) {
                dispatch(new AnwesenheitNotificationJob($parent->sorgorgeberechtigter2, $child->first_name, 'checkOut'));
            }
        }

        Cache::forget('checkedIn'.$child->id);

        return response()->json([
            'success' => true,
        ]);

    }

    /**
     * Marks a child as checked in and dispatches relevant notifications.
     *
     * @param Child \$child Child model whose attendance is updated.
     * @return \Illuminate\Http\JsonResponse JSON response confirming success.
     */
    public function anmelden(Child $child)
    {

        $checkIn = $child->checkIns()
            ->whereDate('date', now()->toDateString())
            ->first();

        if ($child->krankmeldungToday()) {
            $krankmeldung = $child->krankmeldungen()
                ->where(function ($query) {
                    $query->whereDate('start', '<=', today())
                        ->whereDate('ende', '>=', today());
                })
                ->first();
            $krankmeldung->update([
                'ende' => Carbon::now()->subDay(),
            ]);

        }

        if ($checkIn) {
            $checkIn->update([
                'checked_in' => true,
                'checked_out' => false,
            ]);

        } else {
            $child->checkIns()->create([
                'checked_in' => true,
                'checked_out' => false,
                'date' => now()->toDateString(),
            ]);
        }

        $parent = $child->parents()->first();

        if ($child->notification) {

            try {
                dispatch(new AnwesenheitNotificationJob($parent, $child->first_name, 'checkIn'));

                if ($parent->sorgorgeberechtigter2) {

                    dispatch(new AnwesenheitNotificationJob($parent->sorgorgeberechtigter2, $child->first_name, 'checkIn'));
                }
            } catch (\Exception $e) {
                Log::error('Error sending notification: '.$e->getMessage());
            }

        }

        Cache::forget('checkedIn'.$child->id);
        Cache::forget('should_be_today'.$child->id);

        return response()->json([
            'success' => true,
        ]);

    }

    /**
     * beim Aufruf wird für alle Kinder ein CheckIn erstellt
     *
     * @return void
     */
    public function dailyCheckIn()
    {

        if (now()->isWeekend()) {
            return;
        }

        $holidayService = new HolidayService();

        if ($holidayService->isTodayHoliday()) {
            return;
        }

        $children = Child::query()
            ->where('auto_checkIn', true)
            ->get();

        $checkIn = [];
        foreach ($children as $child) {

            if ($child->krankmeldungToday() || ! $child->auto_checkIn) {
                continue;
            }

            $checkIn[] = [
                'child_id' => $child->id,
                'checked_in' => true,
                'checked_out' => false,
                'date' => now()->toDateString(),
            ];

        }

        ChildCheckIn::query()->insert($checkIn);
    }

    public function destroyAbfrage($date)
    {
        if (! auth()->user()->can('edit schickzeiten')) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Sie haben keine Berechtigung für diese Aktion.',
            ]);
        }

        try {
            $date = Carbon::parse($date);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Das Datum konnte nicht gelesen werden.',
            ]);
        }

        ChildCheckIn::query()
            ->whereDate('date', $date->toDateString())
            ->delete();

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Die Abfrage wurde gelöscht.',
        ]);
    }

    public function storeAbfrage(Request $request)
    {
        $request->validate([
            'date_start' => 'required|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'lock_at' => 'nullable|date',
        ]);

        $date_start = Carbon::parse($request->date_start);
        $date_end = $request->date_end ? Carbon::parse($request->date_end) : $date_start->copy();
        $lock_at = $request->lock_at ? Carbon::parse($request->lock_at) : null;

        $careSettings = new CareSetting;

        $children = Child::query()
            ->whereIn('class_id', $careSettings->class_list)
            ->whereIn('group_id', $careSettings->groups_list)
            ->with([
                'checkIns' => function ($query) use ($date_start, $date_end) {
                    $query->whereBetween('date', [$date_start->toDateString(), $date_end->toDateString()]);
                },
                'parents'
            ])
            ->get();

        $checkIns = [];
        $parentsToNotify = collect(); // Sammle Eltern, die benachrichtigt werden sollen
        $holidayService = new HolidayService();

        for ($date = $date_start; $date->lte($date_end); $date->addDay()) {
            if ($date->isWeekend()) {
                continue;
            }

            // Feiertage/Ferien überspringen (Feature 6, Verbesserung D)
            if ($holidayService->isHoliday($date->copy())) {
                continue;
            }

            foreach ($children as $child) {
                // Prüfe, ob bereits ein CheckIn für dieses Datum existiert
                $existingCheckIn = $child->checkIns->where('date', $date->toDateString())->first();

                if ($existingCheckIn) {
                    continue;
                }

                $checkIns[] = [
                    'child_id' => $child->id,
                    'checked_in' => false,
                    'checked_out' => false,
                    'date' => $date->toDateString(),
                    'should_be' => null, // null = noch nicht beantwortet
                    'lock_at' => $lock_at ? $lock_at->toDateString() : $date_start->copy()->subDay()->toDateString(),
                ];

                // Sammle Eltern für Benachrichtigungen (nur einmal pro Elternteil)
                foreach ($child->parents as $parent) {
                    if (!$parentsToNotify->contains('id', $parent->id)) {
                        $parentsToNotify->push($parent);
                    }
                }
            }
        }

        if (!empty($checkIns)) {
            ChildCheckIn::query()->insert($checkIns);

            // Benachrichtige alle betroffenen Eltern einmalig
            $this->notifyParentsAboutNewAttendanceQuery($parentsToNotify, $date_start, $date_end, $lock_at);
        }

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Die Abfrage wurde erstellt.',
        ]);
    }

    /**
     * Benachrichtigt Eltern über eine neue Anwesenheitsabfrage
     */
    private function notifyParentsAboutNewAttendanceQuery($parents, Carbon $dateStart, Carbon $dateEnd, ?Carbon $lockAt)
    {
        foreach ($parents as $parent) {
            $title = 'Neue Anwesenheitsabfrage';

            $dateRange = $dateStart->format('d.m.Y');
            if ($dateStart->toDateString() !== $dateEnd->toDateString()) {
                $dateRange .= ' bis ' . $dateEnd->format('d.m.Y');
            }

            $body = "Es liegt eine neue Anwesenheitsabfrage für Ihre Kinder vor ({$dateRange}).";

            if ($lockAt) {
                $body .= " Bitte antworten Sie bis zum " . $lockAt->format('d.m.Y') . ".";
            } else {
                $body .= " Bitte geben Sie Ihre Rückmeldung.";
            }

            // Sende Notification (enthält bereits 'database' und WebPush Channels)
            try {
                $parent->notify(new AttendanceQueryNotification($title, $body, url('schickzeiten')));
            } catch (\Exception $e) {
                Log::error("Fehler beim Senden der Anwesenheitsabfrage-Benachrichtigung an {$parent->name}: " . $e->getMessage());
            }
        }
    }

    public function downloadAbfrageAnwesenheit(Request $request)
    {
        $request->validate([
            'date_start' => 'required|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
        ]);

        $dates = ChildCheckIn::query()
            ->whereBetween('date', [$request->date_start, $request->date_end])
            ->orderBy('date')
            ->pluck('date')
            ->unique();

        return Excel::download(new AnwesenheitsAbfrageExport($request->date_start, $request->date_end, $dates), 'Anwesenheitsabfrage.xlsx');

    }

    public function getCheckIns(Child $child)
    {
        $checkIns = $child->checkIns()
            ->whereDate('date', '>=', now()->toDateString())
            ->where(function ($query) {
                $query->where('checked_in', false)
                    ->orWhere('checked_out', false);
            })
            ->get();

        if ($checkIns) {
            return response()->json([
                'success' => true,
                'data' => $checkIns,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'data' => null,
            ]);
        }
    }

    public function toogleShouldBe($checkIn)
    {

        if (! auth()->user()->can('edit schickzeiten')) {
            return response()->json([
                'success' => false,
                'message' => 'Sie haben keine Berechtigung für diese Aktion.',
            ]);
        }

        $checkIn = ChildCheckIn::query()
            ->where('id', $checkIn)
            ->first();

        if (! $checkIn) {
            return response()->json([
                'success' => false,
                'message' => 'CheckIn nicht gefunden.',
            ]);
        }

        $checkIn->update([
            'should_be' => ! $checkIn->should_be,
        ]);

        Cache::forget('should_be_today'.$checkIn->child_id);

        return response()->json([
            'success' => true,
            'data' => $checkIn,
        ]);
    }

    public function editMandates(Child $child)
    {
        Gate::authorize('edit schickzeiten');

        return view('child.editMandates', [
            'child' => $child,
            'mandates' => $child->mandates,
        ]);

    }

    public function updateMandates(Request $request, Child $child)
    {

        Gate::authorize('edit schickzeiten');

        $request->validate([
            'mandates' => 'nullable|string',
        ]);

        $Zeilen = preg_split('/\r\n|\r|\n/', $request->mandates);
        $mandates = array_filter(array_map('trim', $Zeilen));

        foreach ($mandates as $mandate) {
            $teile = explode(',', $mandate, 2);
            try {
                $child->mandates()->updateOrCreate([
                    'mandate_name' => trim($teile[0]),
                    'mandate_description' => isset($teile[1]) ? trim($teile[1]) : null,
                    'created_by' => auth()->id(),
                ]);
            } catch (\Exception $e) {
                Log::error('Error updating/creating mandate: '.$e->getMessage());

                return redirect()->back()->with([
                    'type' => 'danger',
                    'Meldung' => 'Fehler beim Speichern der Vollmacht.'.$e->getMessage(),
                ]);
            }
        }

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Die Vollmachten wurden aktualisiert.',
        ]);
    }

    /** Alias, der von der Route aufgerufen wird (route-Name: child.mandates.destroy) */
    public function destroyMandate(Child $child, ChildMandate $childMandate)
    {
        return $this->deleteMandates($child, $childMandate);
    }

    public function deleteMandates(Child $child, ChildMandate $childMandate)
    {
        Gate::authorize('edit schickzeiten');

        if ($child->id !== $childMandate->child_id) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Die Vollmacht gehört nicht zu diesem Kind.',
            ]);
        }

        try {
            $childMandate->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting mandate: '.$e->getMessage());

            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Fehler beim Löschen der Vollmacht.'.$e->getMessage(),
            ]);
        }

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Die Vollmacht wurde gelöscht.',
        ]);
    }

    /**
     * Liefert Statistiken für einen Zeitraum als JSON.
     * Feature 6E: Verwaltungs-Statistiken & Planungshilfe
     */
    public function attendanceStats(Request $request): JsonResponse
    {
        Gate::authorize('edit schickzeiten');

        $request->validate([
            'date_start' => 'required|date',
            'date_end'   => 'required|date|after_or_equal:date_start',
        ]);

        $start = Carbon::parse($request->date_start)->startOfDay();
        $end   = Carbon::parse($request->date_end)->endOfDay();

        $checkIns = ChildCheckIn::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with('child.group')
            ->get();

        $byDate = $checkIns
            ->groupBy(fn ($ci) => $ci->date->format('Y-m-d'))
            ->map(function ($group) {
                return [
                    'total'               => $group->count(),
                    'coming'              => $group->where('should_be', true)->count(),
                    'not_coming'          => $group->whereStrict('should_be', false)->count(),
                    'pending'             => $group->whereNull('should_be')->count(),
                    'actually_checked_in' => $group->where('checked_in', true)->count(),
                ];
            });

        $totalCheckIns = $checkIns->count();
        $totalComing   = $checkIns->where('should_be', true)->count();
        $uniqueDays    = $checkIns->pluck('date')->unique()->count();

        return response()->json([
            'by_date'               => $byDate,
            'by_group'              => $checkIns
                ->groupBy(fn ($ci) => $ci->child->group?->name ?? 'Ohne Gruppe')
                ->map(fn ($g) => [
                    'total_children'    => $g->pluck('child_id')->unique()->count(),
                    'avg_coming_per_day' => round(
                        $g->where('should_be', true)->count()
                        / max($g->pluck('date')->unique()->count(), 1),
                        1
                    ),
                ]),
            'response_rate'         => $totalCheckIns > 0
                ? round($checkIns->whereNotNull('should_be')->count() / $totalCheckIns * 100, 1)
                : 0,
            'forecast_max_children' => $byDate->max('coming') ?? 0,
            'avg_per_day'           => $uniqueDays > 0 ? round($totalComing / $uniqueDays, 1) : 0,
            'pending_count'         => $checkIns->whereNull('should_be')->count(),
            'total'                 => $totalCheckIns,
        ]);
    }

    /**
     * Erstellt und lädt einen Ferienplan als PDF herunter.
     * Feature 6E: PDF-Export für Ferienplan
     */
    public function exportFerienplan(Request $request)
    {
        Gate::authorize('edit schickzeiten');

        $request->validate([
            'date_start' => 'required|date',
            'date_end'   => 'required|date|after_or_equal:date_start',
        ]);

        $checkIns = ChildCheckIn::query()
            ->whereBetween('date', [$request->date_start, $request->date_end])
            ->where('should_be', true)
            ->with(['child.group', 'child.schickzeiten'])
            ->orderBy('date')
            ->get();

        $pdf = Pdf::loadView('pdf.ferienplan', [
            'checkIns' => $checkIns->groupBy(fn ($ci) => $ci->date->format('Y-m-d')),
            'start'    => Carbon::parse($request->date_start),
            'end'      => Carbon::parse($request->date_end),
        ]);

        $pdf->setPaper('A4', 'landscape');

        $filename = 'Ferienplan_'
            . Carbon::parse($request->date_start)->format('d.m.Y')
            . '_bis_'
            . Carbon::parse($request->date_end)->format('d.m.Y')
            . '.pdf';

        return $pdf->download($filename);
    }
}

<?php

namespace App\Http\Controllers;

use App\Exports\ReinigungExport;
use App\Http\Requests\CreateAutoReinigungRequest;
use App\Http\Requests\ReinigungsRequest;
use App\Model\Group;
use App\Model\Reinigung;
use App\Model\ReinigungsTask;
use App\Model\User;
use App\Services\HolidayService;
use App\Settings\ReinigungSetting;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReinigungController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }

    /**
     * Prüft, ob bei den Gruppen überhaupt Bereiche gepflegt sind.
     *
     * Ist dies nicht der Fall, kann der Reinigungsplan nicht sinnvoll getrennt
     * angezeigt/befüllt werden und arbeitet automatisch im gemeinsamen Modus
     * (siehe isCombinedMode()), unabhängig von ReinigungSetting::$separate_bereiche.
     */
    protected function hasConfiguredBereiche(): bool
    {
        return Group::query()
            ->whereNotNull('bereich')
            ->where('bereich', '!=', '')
            ->where('bereich', '!=', 'Aufnahme')
            ->exists();
    }

    /**
     * Ermittelt, ob der Reinigungsplan als ein gemeinsamer Plan für die gesamte
     * Einrichtung geführt werden soll (Pseudo-Bereich Reinigung::BEREICH_GESAMT).
     */
    protected function isCombinedMode(ReinigungSetting $setting): bool
    {
        return ! $setting->separate_bereiche || ! $this->hasConfiguredBereiche();
    }

    /**
     * Schränkt eine Gruppen-Query (bzw. den "groups"-whereHas-Constraint) auf den
     * gewünschten Bereich ein. Im gemeinsamen Modus (Reinigung::BEREICH_GESAMT)
     * werden alle Gruppen berücksichtigt - inklusive Gruppen ohne gepflegten
     * Bereich - mit Ausnahme des Bereichs "Aufnahme" und der über
     * ReinigungSetting::$combined_exclude_bereiche ausgeschlossenen Bereiche.
     */
    protected function applyBereichConstraint(Builder $query, string $bereich, ReinigungSetting $setting): void
    {
        if ($bereich === Reinigung::BEREICH_GESAMT) {
            $excluded = array_merge(['Aufnahme'], $setting->combined_exclude_bereiche);

            $query->where(function ($q) use ($excluded) {
                $q->whereNull('bereich')->orWhereNotIn('bereich', $excluded);
            });
        } else {
            $query->where('bereich', '=', $bereich);
        }
    }

    public function autoCreateStart($bereich)
    {
        $task = ReinigungsTask::all();
        $reinigungSetting = new ReinigungSetting;

        $bereichGroups = Group::query();
        $this->applyBereichConstraint($bereichGroups, $bereich, $reinigungSetting);
        $bereichGroups = $bereichGroups->get();

        if (! auth()->user()->can('edit reinigung')) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        if ($bereichGroups->count() < 1) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Bereich enthält keine Gruppen',
            ]);
        }

        return view('reinigung.autoCreate', [
            'bereich' => $bereichGroups,
            'bereichName' => $bereich,
            'aufgaben' => $task,
            'roles' => Role::all(),
        ]);
    }

    public function autoCreate(CreateAutoReinigungRequest $request, $bereich)
    {

        if (! auth()->user()->can('edit reinigung')) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $reinigungSetting = new ReinigungSetting;

        $start = Carbon::createFromFormat('Y-m-d', $request->start)->startOfWeek();
        $ende = Carbon::createFromFormat('Y-m-d', $request->end)->endOfWeek();

        if (! is_null($request->exclude) and count($request->exclude) > 0 and $request->exclude[0] != 0) {
            $excludeGroups = $request->exclude;
        } else {
            $excludeGroups = [];
        }
        $users = User::query()->whereHas('groups', function ($query) use ($excludeGroups, $bereich, $reinigungSetting) {
            $this->applyBereichConstraint($query, $bereich, $reinigungSetting);
            $query->whereNotIn('groups.id', $excludeGroups);
        })->whereHas('reinigung', function ($query) use ($start, $ende, $bereich) {
            $query->whereBetween('datum', [$start, $ende]);
            if ($bereich !== Reinigung::BEREICH_GESAMT) {
                $query->where('bereich', '=', $bereich);
            }
        }, '<', 1)
            ->withCount('reinigung') // Fairness: Gesamtzahl bisheriger Einsätze je Nutzer
            ->get();

        // Vorschlag 1 - Fairness-Algorithmus: Nutzer mit den wenigsten bisherigen
        // Einsätzen werden zuerst eingeteilt. Innerhalb derselben Einsatzzahl wird
        // weiterhin zufällig gemischt, damit die Verteilung nicht vorhersehbar ist.
        $users_all = $users->groupBy('reinigung_count')
            ->sortKeys()
            ->map(fn ($group) => $group->shuffle())
            ->flatten(1)
            ->values();
        $users_all = $users_all->unique('id');

        if ($users_all->isEmpty()) {
            return redirect(url('reinigung'))->with([
                'type' => 'danger',
                'Meldung' => 'Keine Nutzer für die Aufgaben im gewählten Bereich vorhanden',
            ]);
        }

        // Zähler für die Verteilung innerhalb dieses Laufs (nicht die historische
        // Gesamtzahl): stellt sicher, dass bei Wiederverwendung (siehe unten) immer
        // der/die am wenigsten in diesem Durchlauf eingeteilte Familie drankommt.
        $runCounts = $users_all->mapWithKeys(fn ($u) => [$u->id => 0])->all();

        $tasks = ReinigungsTask::whereIn('id', $request->aufgaben)->get();
        $date = $start->copy();

        // Vorschlag 2 - Ferienausschluss: nur aktiv, wenn per Setting eingeschaltet.
        $holidayService = $reinigungSetting->skip_holidays ? new HolidayService : null;

        while ($date->lte($ende)) {
            if ($holidayService and $holidayService->isHoliday($date)) {
                $date->addWeek();

                continue;
            }

            // In dieser Woche bereits verplante Nutzer-IDs (inkl. sorg1/sorg2), um
            // eine Familie nach Möglichkeit nicht zweimal in derselben Woche
            // einzuteilen.
            $assignedThisWeek = [];

            foreach ($tasks as $task) {
                // Nutzer mit den wenigsten Einsätzen in diesem Lauf wählen, der/die
                // diese Woche noch nicht eingeteilt ist.
                $user = $users_all
                    ->reject(fn ($u) => in_array($u->id, $assignedThisWeek))
                    ->sortBy(fn ($u) => $runCounts[$u->id])
                    ->first();

                // Sind nicht genügend unterschiedliche Familien vorhanden (z. B. nur
                // eine Familie im Bereich), wird die Familie mit den wenigsten
                // Einsätzen trotzdem erneut eingesetzt (Vorschlag: Mehrfach-Einsatz
                // statt Abbruch mit "Nicht genügend Nutzer").
                if (is_null($user)) {
                    $user = $users_all->sortBy(fn ($u) => $runCounts[$u->id])->first();
                }

                $reinigung = new Reinigung;
                $reinigung->bereich = $bereich;
                $reinigung->datum = $date;
                $reinigung->users_id = $user->id;
                $reinigung->aufgabe = $task->task;
                $reinigung->save();

                $runCounts[$user->id]++;
                $assignedThisWeek[] = $user->id;

                if ($user->sorg2 != null) {
                    $assignedThisWeek[] = $user->sorg2;
                }

                if ($user->sorg1 != null) {
                    $assignedThisWeek[] = $user->sorg1;
                }
            }

            $date->addWeek();
        }

        return redirect()->to(url('reinigung'))->with([
            'type' => 'success',
            'Meldung' => 'Plan aktualisiert',
        ]);
    }

    /**
     * @return RedirectResponse|BinaryFileResponse
     */
    public function export($bereich)
    {
        if (auth()->user()->can('edit reinigung')) {
            return Excel::download(new ReinigungExport($bereich), Carbon::now()->format('Y-m-d').'_'.$bereich.'_Reinigung.xlsx');
        }

        return redirect()->back()->with([
            'type' => 'danger',
            'Meldung' => 'Berechtigung fehlt',
        ]);
    }

    /**
     * @return RedirectResponse|void
     */
    public function destroy($Bereich, Reinigung $reinigung)
    {
        $reinigungSetting = new ReinigungSetting;
        $combined = $this->isCombinedMode($reinigungSetting);

        if (auth()->user()->can('edit reinigung') and ($combined or $reinigung->bereich == $Bereich)) {
            $reinigung->delete();

            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Reinigungsaufgabe wurde gelöscht.',
            ]);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $datum = Carbon::now()->startOfWeek()->startOfDay();

        if ($datum->month < 6) {
            $ende = Carbon::createFromFormat('d.m', '30.8');
        } else {
            $ende = Carbon::createFromFormat('d.m', '30.8');
            $ende->addYear();
        }

        $reinigungSetting = new ReinigungSetting;
        $combined = $this->isCombinedMode($reinigungSetting);

        if ($combined) {
            // Gemeinsamer Plan für die gesamte Einrichtung: entweder weil per Setting
            // gewünscht (ReinigungSetting::$separate_bereiche = false), oder weil bei
            // den Gruppen gar keine Bereiche gepflegt sind ("ohne Gruppenbereiche").
            $Bereiche = collect([Reinigung::BEREICH_GESAMT]);
        } elseif (! $user->can('edit reinigung') and ! $user->can('view reinigung')) {
            $user->load('groups');
            $Bereiche = $user->groups->pluck('bereich')->unique();
            $Bereiche = $Bereiche->filter(function ($value) {
                if (! empty($value) and $value != 'Aufnahme') {
                    return $value;
                }
            });
        } else {
            $Bereiche = Group::query()
                ->whereNotNull('bereich')
                ->where('bereich', '!=', '')
                ->where('bereich', '!=', 'Aufnahme')
                ->pluck('bereich')
                ->unique();
        }

        $Reinigung = [];

        foreach ($Bereiche as $Bereich) {
            $query = Reinigung::query()->whereDate('datum', '>=', $datum)->orderBy('datum');

            // Im gemeinsamen Modus werden alle Datensätze angezeigt, unabhängig vom
            // ursprünglich beim Anlegen gespeicherten (echten) Bereich - so bleibt die
            // Historie beim Umschalten zwischen den Modi vollständig erhalten.
            if ($Bereich !== Reinigung::BEREICH_GESAMT) {
                $query->where('bereich', $Bereich);
            }

            $Reinigung[$Bereich] = $query->get();
        }

        return view('reinigung.show', [
            'Bereiche' => $Bereiche,
            'Familien' => $Reinigung,
            'datum' => $datum,
            'user' => $user,
            'ende' => $ende,
            'aufgaben' => ReinigungsTask::all(),
            'combinedMode' => $combined,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|\Illuminate\Contracts\View\View|RedirectResponse
     */
    public function create(Request $request, $Bereich, $Datum)
    {
        if (! $request->user()->can('edit reinigung')) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $reinigungSetting = new ReinigungSetting;
        $combined = $this->isCombinedMode($reinigungSetting);

        $datum = Carbon::createFromFormat('Ymd', $Datum)->startOfWeek()->startOfDay();
        $ende = $datum->copy()->endOfWeek()->endOfDay();

        // Aktueller Zeitraum (Schuljahr) analog zur Planansicht (index()): wird
        // genutzt, um die Nutzerauswahl nach Anzahl der bisherigen Einsätze in
        // diesem Zeitraum zu sortieren (wenigste Einsätze zuerst).
        $periodStart = Carbon::now()->startOfWeek()->startOfDay();
        if ($periodStart->month < 6) {
            $periodEnd = Carbon::createFromFormat('d.m', '30.8');
        } else {
            $periodEnd = Carbon::createFromFormat('d.m', '30.8')->addYear();
        }

        $newusers = User::whereHas('groups', function ($query) use ($Bereich, $reinigungSetting) {
            $this->applyBereichConstraint($query, $Bereich, $reinigungSetting);
        })
            ->withCount(['Reinigung as reinigung_period_count' => function ($query) use ($periodStart, $periodEnd, $Bereich, $combined) {
                $query->whereBetween('datum', [$periodStart, $periodEnd]);
                if (! $combined) {
                    $query->where('bereich', $Bereich);
                }
            }])
            ->get();

        // Vorschlag: erst nach Anzahl der Einsätze im aktuellen Zeitraum (aufsteigend),
        // dann alphabetisch nach Familienname sortieren.
        $newusers = $newusers->sortBy(function ($u) {
            return sprintf('%05d_%s', $u->reinigung_period_count, mb_strtolower((string) $u->familie_name));
        })->values();

        $Reinigung = Reinigung::query()
            ->where('bereich', $Bereich)
            ->whereDate('datum', '>=', $datum->copy()->subWeek())
            ->orderBy('datum')
            ->get();

        $Aufgaben = ReinigungsTask::all();

        return view('reinigung.edit', [
            'Bereich' => $Bereich,
            'Familien' => $Reinigung,
            'datum' => $datum,
            'ende' => $ende,
            'users' => $newusers,
            'aufgaben' => $Aufgaben,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     */
    public function store($Bereich, ReinigungsRequest $request)
    {
        $task = ReinigungsTask::find($request->aufgabe);
        $reinigung = new Reinigung($request->validated());
        $reinigung->bereich = $Bereich;
        $reinigung->aufgabe = $task->task;
        $reinigung->save();

        return redirect()->to(url('reinigung'))->with([
            'type' => 'success',
            'Meldung' => 'Plan aktualisiert',
        ]);
    }
}

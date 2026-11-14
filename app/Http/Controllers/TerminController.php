<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTerminRequest;
use App\Model\Group;
use App\Model\Post;
use App\Model\Reinigung;
use App\Model\Termin;
use App\Repositories\GroupsRepository;
use App\Services\IndividualRecipients;
use App\Settings\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class TerminController extends Controller implements HasMiddleware
{
    private GroupsRepository $grousRepository;

    public function __construct(GroupsRepository $groupsRepository)
    {

        $this->grousRepository = $groupsRepository;
    }

    public static function middleware(): array
    {
        return [
            'password_expired',
        ];
    }

    /**
     * Dürfen Termine laut Einstellung veröffentlicht werden?
     */
    private function publicAllowed(): bool
    {
        return app(GeneralSetting::class)->public_termine_enabled;
    }

    /**
     * Display a listing of all termine.
     *
     * @return View
     */
    public function index()
    {
        $user = auth()->user();
        // B-102: ausgeblendete Termine nur auf Wunsch (?ausgeblendet=1) zeigen, dann zum Wieder-Einblenden.
        $showHidden = request()->boolean('ausgeblendet');

        $query = Termin::query()
            ->with(['groups', 'recipientUsers'])
            ->where(function ($query) {
                $query->where('start', '>=', Carbon::today())
                    ->orWhere('ende', '>=', Carbon::today());
            })
            ->orderBy('start');

        if (! $user->can('view all')) {
            $query->forRecipient($user);
        }

        $hiddenIds = $user->hiddenTermine()->pluck('termine.id')->all();
        $termine = $query->get();
        $hiddenCount = $termine->whereIn('id', $hiddenIds)->count();
        if (! $showHidden) {
            $termine = $termine->whereNotIn('id', $hiddenIds);
        }

        // Eigene Reinigungsdienste der Familie als (nicht gespeicherte) Wochentermine ergänzen
        $termine = $termine
            ->concat(Reinigung::upcomingForFamily($user)->map->toTermin())
            ->sortBy('start')
            ->values();

        return view('termine.index', [
            'termine' => $termine,
            'hiddenIds' => $hiddenIds,
            'hiddenCount' => $hiddenCount,
            'showHidden' => $showHidden,
        ]);
    }

    /**
     * Termin für sich ausblenden (B-102) – gilt auch in der App und im Kalender-Abo.
     */
    public function hide(int $termin): RedirectResponse
    {
        $model = $this->visibleTermin($termin);
        auth()->user()->hiddenTermine()->syncWithoutDetaching([$model->id]);
        Cache::forget('termine'.auth()->id());

        return back()->with([
            'type' => 'success',
            'Meldung' => 'Der Termin „'.$model->terminname.'“ wird Ihnen nicht mehr angezeigt.',
        ]);
    }

    /**
     * Ausgeblendeten Termin wieder anzeigen (B-102).
     */
    public function unhide(int $termin): RedirectResponse
    {
        $model = $this->visibleTermin($termin);
        auth()->user()->hiddenTermine()->detach($model->id);
        Cache::forget('termine'.auth()->id());

        return back()->with([
            'type' => 'success',
            'Meldung' => 'Der Termin „'.$model->terminname.'“ wird wieder angezeigt.',
        ]);
    }

    private function visibleTermin(int $id): Termin
    {
        $query = Termin::query()->whereKey($id);
        if (! auth()->user()->can('view all')) {
            $query->forRecipient(auth()->user());
        }

        return $query->firstOrFail();
    }

    public function edit(Termin $termine)
    {
        // Gleiche Berechtigung wie update() – vorher konnte jedes Konto jeden Termin samt Empfängern einsehen
        abort_unless(auth()->user()->can('edit termin'), 403, 'Berechtigung fehlt');

        $groups = Cache::remember('groups', now()->addDay(), function () {
            return Group::active()->get();
        });

        return view('termine.edit', [
            'gruppen' => $groups,
            'termin' => $termine,
        ]);
    }

    public function update(CreateTerminRequest $request, Termin $termine)
    {
        if (! auth()->user()->can('edit termin')) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        try {
            $start = Carbon::parse($request->start);
            $ende = Carbon::parse($request->ende);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Fehler beim Erstellen des Termins. Ungültiges Datumsformat. Bitte verwenden Sie das Format: dd.mm.yyyy hh:mm:ss',
            ]);
        }

        try {
            $termine->fill(
                [
                    'terminname' => $request->terminname,
                    'start' => $start,
                    'ende' => $ende,
                    'fullDay' => $request->fullDay,
                    'public' => $this->publicAllowed() && $request->boolean('public'),
                ]
            );
            \App\Support\AudienceField::apply($termine, $request);
            $termine->save();
        } catch (\Exception $e) {

            Log::error('Termin: Fehler beim Aktualisieren des Termins: '.$e->getMessage());

            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Fehler beim Aktualisieren des Termins.',
            ]);
        }

        try {
            $gruppen = $this->grousRepository->getGroups($request->input('gruppen'));
            $termine->groups()->sync($gruppen);
            IndividualRecipients::sync($termine, $request);

        } catch (\Exception $e) {

            Log::error('Termin: Fehler beim Aktualisieren der Gruppen: '.$e->getMessage());

            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Fehler beim Aktualisieren der Gruppen.',
            ]);
        }

        Cache::forget('termine'.auth()->id());

        return redirect(url('/termine'))->with([
            'type' => 'success',
            'Meldung' => 'Termin aktualisiert.',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function create()
    {
        if (! Gate::authorize('create', Termin::class)) {
            return redirect()->to(url('home'))->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        return view('termine.create', [
            'gruppen' => Group::active()->get(),
        ]);
    }

    public function createFromPost(Post $post)
    {
        if (! Gate::authorize('create', Termin::class)) {
            return redirect()->to(url('home'))->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung Termine zu erstellen fehlt',
            ]);
        }

        $pattern = '/^(0?[1-9]|[12]\d|3[01]).(0?[1-9]|1[0-2]).([12]\d{3})$/';

        $matches = [];
        $termin = preg_match($pattern, $post->header, $matches);

        if (! $termin) {
            $termin = preg_match($pattern, $post->news, $matches);
        }

        if (! $termin) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Kein Datum im Beitrag gefunden.',
            ]);
        }

        $terminname = $post->header;
        $terminname = str_replace($matches[0], '', $terminname);

        $start = Carbon::parse($matches[0]);
        $ende = Carbon::parse($matches[0]);

        $termin = new Termin([
            'terminname' => $terminname,
            'start' => $start,
            'ende' => $ende,
            'fullDay' => false,
            'public' => false,
        ]);

        return view('termine.createFromPost', [
            'gruppen' => Group::active()->get(),
            'termin' => $termin,
            'post' => $post,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function store(CreateTerminRequest $request)
    {
        Gate::authorize('create', Termin::class);

        try {
            $start = Carbon::parse($request->start);
            $ende = Carbon::parse($request->ende);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Fehler beim Erstellen des Termins. Ungültiges Datumsformat. Bitte verwenden Sie das Format: dd.mm.yyyy hh:mm:ss',
            ]);
        }

        if ($start->day != $ende->day) {
            $start = $start->startOfDay();
            $ende = $ende->endOfDay();
        }

        try {
            $termin = new Termin([
                'terminname' => $request->terminname,
                'start' => $start,
                'ende' => $ende,
                'fullDay' => $request->fullDay,
                'public' => $this->publicAllowed() && $request->boolean('public'),
            ]);
            \App\Support\AudienceField::apply($termin, $request);
            $termin->save();

            $gruppen = $request->input('gruppen');
            $gruppen = $this->grousRepository->getGroups($gruppen);

            $termin->groups()->attach($gruppen);
            IndividualRecipients::sync($termin, $request);

            // „nur für Schüler“: Eltern werden nicht benachrichtigt
            if (! $termin->isStudentOnly()) {
                $users = $termin->recipients();

                $termin->notify(
                    users: $users,
                    title: 'Neuer Termin',
                    message: 'Neuer Termin: '.$termin->terminname,
                    type: 'Termine');
            }
            \App\Services\Students\StudentNotifier::terminCreated($termin);

            Cache::forget('termine'.auth()->id());
        } catch (\Exception $e) {
            Log::error('Termin: Fehler beim Erstellen des Termins: '.$e->getMessage());

            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Fehler beim Erstellen des Termins.',
            ]);
        }

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Termin erstellt.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function destroy(Termin $termine)
    {
        Gate::authorize('delete', $termine);

        $termine->groups()->detach();
        $termine->recipientUsers()->detach();
        $termine->delete();

        Cache::forget('termine'.auth()->id());

        return redirect(url('/termine'))->with([
            'type' => 'success',
            'Meldung' => 'Termin gelöscht.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Arbeitsgemeinschaften;

use App\Exports\ParticipantsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Arbeitsgemeinschaften\CreateGTARequest;
use App\Http\Requests\Arbeitsgemeinschaften\EditGTARequest;
use App\Model\Arbeitsgemeinschaft;
use App\Model\Child;
use App\Model\Group;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class GTAController extends Controller
{
    protected $weekdays = [
        1 => 'Montag',
        2 => 'Dienstag',
        3 => 'Mittwoch',
        4 => 'Donnerstag',
        5 => 'Freitag',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $arbeitsgemeinschaften = Arbeitsgemeinschaft::with(['manager', 'groups', 'participants'])
            ->where('end_date', '>', now())
            ->get();

        return view('arbeitsgemeinschaften.verwaltung')
            ->with([
                'arbeitsgemeinschaften' => $arbeitsgemeinschaften,
                'weekdays' => $this->weekdays,
            ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $managers = User::role('Mitarbeiter')->orderBy('name')->get();
        $groups = Group::all();

        return view('arbeitsgemeinschaften.create')
            ->with([
                'managers' => $managers,
                'groups' => $groups,
                'weekdays' => $this->weekdays,
            ]);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(createGTARequest $request)
    {
        $gta = new Arbeitsgemeinschaft($request->validated());
        $gta->save();

        // Gleichnamige Gruppe erstellen und verknüpfen
        $group = Group::create([
            'name' => $gta->name,
        ]);

        if ($request->groups) {
            $gta->groups()->attach($request->groups);
        }

        return redirect()
            ->route('verwaltung.arbeitsgemeinschaften.index')
            ->with([
                'type' => 'success',
                'Meldung' => 'Die Arbeitsgemeinschaft wurde erfolgreich erstellt.',
            ]);

    }

    /**
     * Display the specified resource.
     */
    public function show(GTA $gTA)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Arbeitsgemeinschaft $arbeitsgemeinschaft)
    {
        $managers = User::role('Mitarbeiter')->orderBy('name')->get();
        $groups = Group::all();

        return view('arbeitsgemeinschaften.edit')
            ->with([
                'arbeitsgemeinschaft' => $arbeitsgemeinschaft,
                'managers' => $managers,
                'groups' => $groups,
                'weekdays' => $this->weekdays,
            ]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EditGTARequest $request, Arbeitsgemeinschaft $arbeitsgemeinschaft)
    {

        $arbeitsgemeinschaft->update($request->validated());
        $arbeitsgemeinschaft->groups()->sync($request->groups);

        return redirect()
            ->route('verwaltung.arbeitsgemeinschaften.index')
            ->with([
                'type' => 'success',
                'Meldung' => 'Die Arbeitsgemeinschaft wurde erfolgreich aktualisiert.',
            ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Arbeitsgemeinschaft $arbeitsgemeinschaft)
    {

        if (! auth()->user()->can('edit GTA')) {
            return redirect()
                ->back()
                ->with([
                    'type' => 'error',
                    'Meldung' => 'Sie haben keine Berechtigung, diese Arbeitsgemeinschaft zu löschen.',
                ]);
        }

        $group = Group::query()
            ->where('name', $arbeitsgemeinschaft->name)
            ->first();
        if ($group) {
            // Lösche die Gruppe, die mit der Arbeitsgemeinschaft verknüpft ist
            $group->users()->detach(); // Entferne alle Nutzer aus der Gruppe
            $group->delete(); // Lösche die Gruppe selbst
        }

        $arbeitsgemeinschaft->delete();

        return redirect()
            ->route('verwaltung.arbeitsgemeinschaften.index')
            ->with([
                'type' => 'success',
                'Meldung' => 'Die Arbeitsgemeinschaft wurde erfolgreich gelöscht.',
            ]);
    }

    public function showParticipants(Arbeitsgemeinschaft $arbeitsgemeinschaft)
    {
        $participants = $arbeitsgemeinschaft->participants()
            ->with(['group', 'class'])
            ->orderBy('last_name')
            ->get();

        // Hole alle Kinder, die in den erlaubten Gruppen sind
        $availableChildren = Child::query()
            ->where(function ($query) use ($arbeitsgemeinschaft) {
                $query->whereHas('group', function ($q) use ($arbeitsgemeinschaft) {
                    $q->whereIn('groups.id', $arbeitsgemeinschaft->groups->pluck('id'));
                })->orWhereHas('class', function ($q) use ($arbeitsgemeinschaft) {
                    $q->whereIn('groups.id', $arbeitsgemeinschaft->groups->pluck('id'));
                });
            })

            ->whereDoesntHave('arbeitsgemeinschaften', function ($query) use ($arbeitsgemeinschaft) {
                $query->where('arbeitsgemeinschaften.id', $arbeitsgemeinschaft->id);
            })
            ->get();

        return view('arbeitsgemeinschaften.teilnehmer', compact('arbeitsgemeinschaft', 'participants', 'availableChildren'));
    }

    public function addParticipant(Request $request, Arbeitsgemeinschaft $arbeitsgemeinschaft)
    {

        $validated = $request->validate([
            'child_id' => 'required|exists:children,id',
        ]);

        if ($arbeitsgemeinschaft->participants()->count() >= $arbeitsgemeinschaft->max_participants) {
            return redirect()
                ->route('verwaltung.arbeitsgemeinschaften.teilnehmer', $arbeitsgemeinschaft)
                ->with('error', 'Die maximale Teilnehmerzahl ist bereits erreicht.');
        }

        if ($arbeitsgemeinschaft->participants->where('id', $validated['child_id'])->isNotEmpty()) {
            return redirect()
                ->route('verwaltung.arbeitsgemeinschaften.teilnehmer', $arbeitsgemeinschaft)
                ->with([
                    'type' => 'error',
                    'Meldung' => 'Das Kind ist bereits Teilnehmer dieser Arbeitsgemeinschaft.',
                ]);
        } else {
            $arbeitsgemeinschaft->participants()->attach($validated['child_id'], [
                'user_id' => auth()->id(),
            ]);
        }

        // Sorgeberechtigte der Gruppe hinzufügen
        $child = Child::find($validated['child_id']);
        $parents = $child->parents;

        // Nur die automatisch erstellte AG-Gruppe verwenden
        $agGroup = Group::query()->where('name', $arbeitsgemeinschaft->name)->first();

        if ($agGroup) {
            foreach ($parents as $parent) {
                // Prüfen, ob der Elternteil bereits in der Gruppe ist
                if (! $agGroup->users()->where('users.id', $parent->id)->exists()) {
                    $agGroup->users()->attach($parent->id);
                }

                if ($parent->sorg2 != null && ! $agGroup->users()->where('users.id', $parent->sorg2)->exists()) {
                    // Füge den zweiten Sorgeberechtigten hinzu, falls vorhanden
                    $agGroup->users()->attach($parent->sorg2);
                }
            }

        }

        return redirect()
            ->route('verwaltung.arbeitsgemeinschaften.teilnehmer', $arbeitsgemeinschaft)
            ->with('success', 'Teilnehmer wurde hinzugefügt.');
    }

    public function removeParticipant(Arbeitsgemeinschaft $arbeitsgemeinschaft, Child $child)
    {
        $arbeitsgemeinschaft->participants()->detach($child->id);

        // Sorgeberechtigte aus der Gruppe entfernen
        $parents = $child->parents;
        $agGroup = Group::query()->where('name', $arbeitsgemeinschaft->name)->first();
        if ($agGroup) {
            foreach ($parents as $parent) {
                $agGroup->users()->detach($parent->id);
                if ($parent->sorg2 != null) {
                    // Entferne den zweiten Sorgeberechtigten, falls vorhanden
                    $agGroup->users()->detach($parent->sorg2);
                }
            }
        }

        return redirect()
            ->route('verwaltung.arbeitsgemeinschaften.teilnehmer', $arbeitsgemeinschaft)
            ->with('success', 'Teilnehmer wurde entfernt.');
    }

    public function exportParticipants(Arbeitsgemeinschaft $arbeitsgemeinschaft)
    {
        $fileName = 'teilnehmer_'.Str::slug($arbeitsgemeinschaft->name).'_'.date('Y-m-d').'.xlsx';

        return Excel::download(new ParticipantsExport($arbeitsgemeinschaft), $fileName);
    }
}

<?php

namespace App\Http\Controllers\Arbeitsgemeinschaften;

use App\Http\Controllers\Controller;
use App\Mail\NeuerTeilnehmerMail;
use App\Model\Arbeitsgemeinschaft;
use App\Model\Group;
use App\Notifications\PushNews;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ArbeitsgemeinschaftController extends Controller
{
    public function index(Request $request)
    {
        $weekdays = [
            1 => 'Montag',
            2 => 'Dienstag',
            3 => 'Mittwoch',
            4 => 'Donnerstag',
            5 => 'Freitag',
        ];

        $children = auth()->user()->children();

        $arbeitsgemeinschaften = Arbeitsgemeinschaft::query()
            ->with(['manager', 'groups', 'participants'])
            ->where('end_date', '>', now())
            ->whereHas('groups', function ($query) use ($children) {
                $query->whereIn('groups.id', $children->pluck('group_id'))
                    ->orWhereIn('groups.id', $children->pluck('class_id'));
            })
            ->get();

        $availableChildrenByAg = [];
        foreach ($arbeitsgemeinschaften as $ag) {
            $availableChildrenByAg[$ag->id] = auth()->user()->children()
                ->filter(function ($child) use ($ag) {
                    return ($ag->groups->pluck('id')->intersect($child->group_id)->isNotEmpty() or $ag->groups->pluck('id')->intersect($child->class_id)->isNotEmpty())
                        && ! $ag->participants->contains($child->id);
                });
        }

        return view('arbeitsgemeinschaften.eltern.index', [
            'arbeitsgemeinschaften' => $arbeitsgemeinschaften,
            'weekdays' => $weekdays,
            'availableChildrenByAg' => $availableChildrenByAg,

        ]);
    }

    public function anmelden(Request $request, Arbeitsgemeinschaft $arbeitsgemeinschaft)
    {
        $request->validate([
            'child_id' => 'required|exists:children,id',
        ]);

        // Prüfen ob das Kind zum eingeloggten User gehört
        $child = auth()->user()->children()->find($request->child_id);
        if (! $child) {
            return back()->with(
                [
                    'type' => 'danger',
                    'Meldung' => 'Das Kind gehört nicht zu Ihnen.',
                ]
            );
        }

        // Prüfen ob das Kind in einer der erlaubten Gruppen ist
        if (! $arbeitsgemeinschaft->groups->pluck('id')->intersect($child->group_id)->isNotEmpty() and ! $arbeitsgemeinschaft->groups->pluck('id')->intersect($child->class_id)->isNotEmpty()) {
            return back()->with(
                [
                    'type' => 'danger',
                    'Meldung' => 'Das Kind gehört nicht zu einer der erlaubten Gruppen.',
                ]
            );
        }

        // Prüfen ob noch Plätze frei sind
        if ($arbeitsgemeinschaft->participants->count() >= $arbeitsgemeinschaft->max_participants) {
            return back()->with(
                [
                    'type' => 'danger',
                    'Meldung' => 'Die maximale Teilnehmerzahl ist bereits erreicht.',
                ]
            );
        }

        if ($arbeitsgemeinschaft->participants->contains($child->id)) {
            return back()->with(
                [
                    'type' => 'danger',
                    'Meldung' => 'Das Kind ist bereits angemeldet.',
                ]
            );
        }

        // Kind anmelden
        $arbeitsgemeinschaft->participants()->attach($request->child_id, [
            'user_id' => auth()->id(),
        ]);

        /*
         * Die Eltern der gruppe hinzufügen
         *
         */
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

        // E-Mail an den AG-Leiter senden
        try {
            Mail::to($arbeitsgemeinschaft->manager->email)
                ->queue(mailable: new NeuerTeilnehmerMail($arbeitsgemeinschaft, $child));
        } catch (\Exception $e) {
            // Fehler beim E-Mail-Versand loggen, aber nicht den Prozess abbrechen
            Log::error('Fehler beim Senden der E-Mail an AG-Leiter: '.$e->getMessage());
            $arbeitsgemeinschaft->manager->notify(new PushNews(
                'Neuer Teilnehmer in Ihrer AG',
                'Ein neues Kind hat sich für Ihre AG angemeldet: '.$child->first_name.' '.$child->last_name

            ));
        }

        return back()->with(
            [
                'type' => 'success',
                'Meldung' => 'Das Kind wurde erfolgreich angemeldet.',
            ]
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChildNotificationRequest;
use App\Http\Requests\CreateChildRequest;
use App\Model\Child;
use App\Model\Group;
use App\Model\Schickzeiten;
use App\Model\User;
use App\Services\Family\GuardianshipService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ChildController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('can:edit schickzeiten', only: ['index', 'create', 'createFromSchickzeit', 'edit', 'updateGuardianPhone']),
        ];
    }

    public function index()
    {
        $childs = Child::query()
            ->with(['group', 'class', 'parents'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $duplicateIds = [];
        foreach ($childs->groupBy(fn (Child $child): string => mb_strtolower(trim($child->last_name))) as $sameLastName) {
            foreach ($sameLastName as $index => $child) {
                $firstName = mb_strtolower(trim($child->first_name));
                foreach ($sameLastName->slice($index + 1) as $otherChild) {
                    $otherFirstName = mb_strtolower(trim($otherChild->first_name));
                    $sameFirstName = $firstName === $otherFirstName
                        || str_starts_with($firstName, $otherFirstName.' ')
                        || str_starts_with($otherFirstName, $firstName.' ');

                    if ($sameFirstName) {
                        $duplicateIds[] = $child->id;
                        $duplicateIds[] = $otherChild->id;
                    }
                }
            }
        }
        $duplicateIds = array_values(array_unique($duplicateIds));

        return view('child.index', [
            'children' => $childs,
            'duplicateIds' => $duplicateIds,
            'statuses' => [
                Child::STATUS_ACTIVE => 'Aktiv',
                Child::STATUS_APPLICANT => 'Bewerber',
                Child::STATUS_LEFT => 'Ausgeschieden',
            ],
        ]);
    }

    public function store(CreateChildRequest $request)
    {

        $child = Child::query()
            ->whereLike('first_name', '%'.$request->first_name.'%')
            ->whereLike('last_name', '%'.$request->last_name.'%')
            ->where('group_id', $request->group_id)
            ->first();

        if ($child) {
            return redirect()->back()->with([
                'Meldung' => 'Kind existiert bereits',
                'type' => 'danger',
            ]);
        }

        $guardianship = app(GuardianshipService::class);

        if (! $request->has('parent_id')) {
            $child = Child::create($request->safe()->except(['parent_id']));
            $guardianship->link($child, auth()->user());
        } else {
            $parent = User::find($request->parent_id);
            $child = Child::create($request->safe()->except(['parent_id']));
            $guardianship->link($child, $parent);

            if (session()->has('schickzeiten')) {
                $schickzeit = session()->get('schickzeiten');

                $schickzeitenQuery = Schickzeiten::query()
                    ->where('child_name', $schickzeit->child_name)
                    ->whereIn('users_id', $schickzeit->user?->familyUserIds() ?? [$schickzeit->users_id]);

                $schickzeitenQuery->update([
                    'child_id' => $child->id,
                ]);
                session()->forget('schickzeiten');
            }
        }

        return redirect()->back()->with([
            'Meldung' => 'Kind wurde erfolgreich erstellt',
            'type' => 'success',
        ]);
    }

    public function create($child = null)
    {

        $parents = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Eltern')->where('guard_name', 'web');
            })
            ->get();

        return view('child.create', [
            'child' => $child ?? new Child,
            'groups' => Group::active()->get(),
            'parents' => $parents,
        ]);
    }

    public function createFromSchickzeit(Schickzeiten $schickzeiten)
    {

        session()->put('schickzeiten', $schickzeiten);

        $parents = $schickzeiten->user;

        if (! $parents) {
            $parents = User::query()
                ->whereHas('role', function ($query) {
                    $query->where('name', 'Eltern');
                })
                ->get();

            $groups = Group::active()->get();

        } else {

            $groups = $parents->groups;
            $parents = collect([$parents]);
        }

        $child = new Child;
        $child->first_name = $schickzeiten->child_name;

        return view('child.create', [
            'child' => $child,
            'groups' => $groups,
            'parents' => $parents,
        ]);
    }

    public function edit(Child $child)
    {
        // Kandidaten für Bezugspersonen: alle aktiven Konten (Großeltern etc. haben
        // nicht zwingend die Rolle „Eltern“)
        $parents = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        return view('child.edit', [
            'child' => $child->load('parents'),
            'groups' => Group::active()->get(),
            'parents' => $parents,
        ]);
    }

    public function updateGuardianPhone(Request $request, Child $child, User $guardian)
    {
        $data = $request->validate([
            'phone' => ['present', 'nullable', 'string', 'max:50'],
        ]);

        $isCurrentCustodian = $child->parents()
            ->whereKey($guardian->id)
            ->wherePivot('has_custody', true)
            ->where(function ($query) {
                $query->whereNull('child_user.valid_until')
                    ->orWhereDate('child_user.valid_until', '>=', today());
            })
            ->exists();

        abort_unless($isCurrentCustodian, 404);

        $guardian->phone = $data['phone'] ?? null;
        $guardian->save();

        return redirect()->back()->with([
            'Meldung' => 'Nichtöffentliche Telefonnummer für '.$guardian->name.' gespeichert',
            'type' => 'success',
        ]);
    }

    public function update(CreateChildRequest $request, Child $child)
    {
        if ($request->user()->cannot('manage', $child)) {
            return redirect()->back()->with([
                'Meldung' => 'Sie haben keine Berechtigung',
                'type' => 'danger',
            ]);
        }

        if (auth()->user()->can('edit schickzeiten') && $request->has('parent_id')) {
            // Nur ergänzen – bestehende Bezugspersonen (inkl. UCS-Verknüpfungen) bleiben erhalten.
            app(GuardianshipService::class)->link($child, User::findOrFail($request->parent_id));
        }

        $child->update(
            $request->only([
                'first_name',
                'last_name',
                'group_id',
                'class_id',
                'auto_checkIn',
            ])
        );

        // Nach dem Update prüfen, ob das Kind noch im Care-Modul ist.
        // Falls nicht, werden alle Schickzeiten des Kindes automatisch gelöscht.
        $careSettings = new \App\Settings\CareSetting;
        $isInCare = in_array($child->group_id, $careSettings->groups_list)
            && in_array($child->class_id, $careSettings->class_list);

        if (! $isInCare) {
            $deletedCount = \App\Model\Schickzeiten::where('child_id', $child->id)->count();
            if ($deletedCount > 0) {
                \App\Model\Schickzeiten::where('child_id', $child->id)->each(function ($sz) {
                    $sz->delete();
                });
                \Illuminate\Support\Facades\Log::info(
                    "Schickzeiten für Kind {$child->first_name} {$child->last_name} (ID: {$child->id}) automatisch gelöscht – Kind ist nicht mehr im Care-Modul.",
                    ['deleted_count' => $deletedCount]
                );
                return redirect()->back()->with([
                    'Meldung' => "Kind wurde erfolgreich bearbeitet. Da das Kind nicht mehr im Care-Modul ist, wurden {$deletedCount} Schickzeit(en) automatisch gelöscht.",
                    'type' => 'warning',
                ]);
            }
        }

        return redirect()->back()->with([
            'Meldung' => 'Kind wurde erfolgreich bearbeitet',
            'type' => 'success',
        ]);
    }

    public function destroy(Child $child)
    {
        if (auth()->user()->cannot('edit schickzeiten')) {
            return redirect()->back()->with([
                'Meldung' => 'Sie haben keine Berechtigung',
                'type' => 'danger',
            ]);
        }

        $child->delete();

        return redirect()->back()->with([
            'Meldung' => 'Kind wurde erfolgreich gelöscht',
            'type' => 'success',
        ]);
    }

    public function setNotification(ChildNotificationRequest $request, Child $child)
    {
        if (auth()->user()->can('manage', $child)) {

            $child->notification = $request->notification;
            $child->save();

            return response()->json([
                'message' => 'Benachrichtigung wurde erfolgreich geändert',
                'notification' => $child->notification,
            ], 201);
        }

        return response()->json([
            'message' => 'Sie haben keine Berechtigung',
        ], 403);

    }

    public function storeMandate(Request $request, Child $child)
    {

        if (auth()->user()->cannot('manage', $child)) {
            return redirect()->back()->with([
                'Meldung' => 'Sie haben keine Berechtigung',
                'type' => 'danger',
            ]);
        }

        $request->validate([
            'mandate_name' => 'required|string|max:255',
            'mandate_description' => 'nullable|string',
        ]);

        $child->mandates()->create([
            'mandate_name' => $request->mandate_name,
            'mandate_description' => $request->mandate_description,
            'created_by' => auth()->id(),
        ]);

        return redirect()->back()->with([
            'Meldung' => 'Mandat wurde erfolgreich erstellt',
            'type' => 'success',
        ]);
    }

    public function destroyMandate(Request $request, Child $child, $mandateId)
    {

        if (auth()->user()->cannot('manage', $child)) {
            return redirect()->back()->with([
                'Meldung' => 'Sie haben keine Berechtigung für diese Aktion',
                'type' => 'danger',
            ]);
        }

        $mandate = $child->mandates()->where('id', $mandateId)->first();

        if (! $mandate) {
            return redirect()->back()->with([
                'Meldung' => 'Vollmacht nicht gefunden',
                'type' => 'danger',
            ]);
        }

        $mandate->delete();

        return redirect()->back()->with([
            'Meldung' => 'Vollmacht wurde erfolgreich gelöscht',
            'type' => 'success',
        ]);
    }
}

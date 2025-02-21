<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChildNotificationRequest;
use App\Http\Requests\CreateChildRequest;
use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\Group;
use App\Model\Schickzeiten;
use App\Model\User;
use Illuminate\Support\Facades\Log;

class ChildController extends Controller
{
    public function __construct()
    {

    }

    public function index()
    {
        $this->middleware('auth');
        $this->middleware('can:edit Schickzeiten');

        $childs = Child::query()
            ->with(['group', 'class', 'parents'])
            ->get();

        return view('child.index', [
            'children' => $childs,
        ]);
    }

    public function store(CreateChildRequest $request)
    {
        $this->middleware('auth');

        if (!$request->has('parent_id')) {
            auth()->user()->children_rel()->create($request->validated());
        } else {
            $parent = User::find($request->parent_id);
            $child = $parent->children_rel()->create($request->validated());


            if (session()->has('schickzeiten')) {
                $schickzeit = session()->get('schickzeiten');

                $schickzeitenQuery = Schickzeiten::query()
                    ->where('child_name', $schickzeit->child_name)
                    ->where(function ($query) use ($schickzeit) {
                        $query->where('users_id', $schickzeit->users_id);
                        if (isset($schickzeit->user->sorg2)) {
                            $query->orWhere('users_id', $schickzeit->user->sorg2);
                        }
                    });


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
        $this->middleware('auth');
        $this->middleware('can:edit Schickzeiten');

        $parents = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Eltern')->where('guard_name', 'web');
            })
            ->get();

        return view('child.create', [
            'child' => $child ?? new Child(),
            'groups' => Group::all(),
            'parents' => $parents,
        ]);
    }

    public function createFromSchickzeit(Schickzeiten $schickzeiten)
    {

        $this->middleware('auth');
        $this->middleware('can:edit Schickzeiten');

        session()->put('schickzeiten', $schickzeiten);

        $parents = $schickzeiten->user;


        if (!$parents) {
            $parents = User::query()
                ->whereHas('role', function ($query) {
                    $query->where('name', 'Eltern');
                })
                ->get();

            $groups = Group::all();

        } else {

            $groups = $parents->groups;
            $parents = collect([$parents]);
        }


        $child = new Child();
        $child->first_name = $schickzeiten->child_name;

        return view('child.create', [
            'child' => $child,
            'groups' => $groups,
            'parents' => $parents
        ]);
    }

    public function edit(Child $child)
    {
        $this->middleware('auth');
        $this->middleware('can:edit Schickzeiten');

        $parents = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Eltern')->where('guard_name', 'web');
            })
            ->get();

        return view('child.edit', [
            'child' => $child,
            'groups' => Group::all(),
            'parents' => $parents,
        ]);
    }

    public function update(CreateChildRequest $request, Child $child)
    {
        $this->middleware('auth');
        $this->middleware('can:edit Schickzeiten');

        $child->update($request->validated());
        if (!$child->parents->contains($request->parent_id)) {
            $child->parents()->sync($request->parent_id);
        }

        $child->update($request->validated());


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
        if (auth()->user()->children()->contains($child)) {

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

}

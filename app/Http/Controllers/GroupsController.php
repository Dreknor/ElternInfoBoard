<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateGroupRequest;
use App\Model\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class GroupsController extends Controller
{

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        if (auth()->user()->can('view groups')) {
            $groups = Group::with('users')->get();
        } else {
            $groups = auth()->user()->groups;
        }

        return view('groups.index')->with([
            'groups' => $groups,
        ]);
    }

    /**
     * erstellt neue Gruppe
     *
     * @param CreateGroupRequest $createGroupRequest
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(CreateGroupRequest $createGroupRequest)
    {
        if (! auth()->user()->can('view groups')) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt.',
            ]);
        }

        $group = new Group($createGroupRequest->validated());
        $group->save();

        Cache::forget('groups');
        Cache::remember('groups', 60 * 5, function () {
            return Group::all();
        });

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Gruppe wurde erstellt',
        ]);
    }

    /**
     *
     * Löscht die angegebene Gruppe
     *
     * @param Request $request
     * @param Group $group
     * @return \Illuminate\Http\RedirectResponse|void
     */
    public function delete(Request $request, Group $group)
    {
        if (! auth()->user()->can('delete groups')) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt.',
            ]);
        }

        if (Hash::check($request->passwort, auth()->user()->password)) {
            $request->validate([
                'passwort' => ['required', 'string'],
            ]);

            $group->users()->sync([]);
            $group->posts()->sync([]);
            $group->termine()->sync([]);
            $group->listen()->sync([]);
            $group->media()->delete();
            $group->delete();

            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Gruppe wurde gelöscht.',
            ]);
        }
    }
}

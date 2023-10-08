<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddUserToOwnGroupRequest;
use App\Http\Requests\CreateGroupRequest;
use App\Http\Requests\CreateOwnGroupRequest;
use App\Model\Group;
use App\Model\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class GroupsController extends Controller
{

    /**
     * @return View
     */
    public function index()
    {
        if (auth()->user()->can('edit groups')) {
            $groups = Group::with('users')->get();
        } elseif (auth()->user()->can('view groups')) {
            $groups = auth()->user()->groups;
            $groups = $groups->merge(auth()->user()->ownGroups);
        } else {
            return redirect(url('/'))->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt'
            ]);
        }

        return view('groups.index')->with([
            'groups' => $groups,
        ]);
    }

    /**
     * erstellt neue Gruppe
     *
     * @param CreateGroupRequest $createGroupRequest
     * @return RedirectResponse
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

    public function storeOwnGroup(CreateOwnGroupRequest $request)
    {
        $group = new Group($request->validated());
        $group->owner_id = auth()->user()->id;
        $group->save();


        Cache::forget('groups');

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Gruppe wurde erstellt',
        ]);

    }

    public function addUserToOwnGroup(Group $group)
    {
        if ($group->owner->id !== auth()->id()) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt.',
            ]);
        }


        return view('groups.addUser')->with([
            'group' => $group,
            'users' => User::whereDoesntHave('groups', function (Builder $query) use ($group) {
                $query->where('group_id', $group->id);
            })->get(),
        ]);
    }

    public function storeUserToOwnGroup(AddUserToOwnGroupRequest $request, Group $group)
    {
        if ($group->owner->id !== auth()->id()) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt.',
            ]);
        }

        $group->users()->syncWithoutDetaching($request->user_id);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Benutzer wurde hinzugefügt.',
        ]);
    }

    public function removeUserFromOwnGroup(Request $request, Group $group)
    {
        if ($group->owner->id !== auth()->id()) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt.',
            ]);
        }

        if (!$group->users->contains($request->user_id)) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Benutzer ist nicht in der Gruppe.',
            ]);
        }

        $group->users()->detach($request->user_id);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Benutzer wurde entfernt.',
        ]);
    }

    /**
     *
     * Löscht die angegebene Gruppe
     *
     * @param Request $request
     * @param Group $group
     * @return RedirectResponse|void
     */
    public function delete(Request $request, Group $group)
    {
        if (!auth()->user()->can('delete groups') and $group->owner_id !== auth()->id()) {
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

    public function deletePrivateGroups()
    {
        $groups = Group::whereNot('owner_id', null)->get();
        foreach ($groups as $group) {
            $group->users()->sync([]);
            $group->posts()->sync([]);
            $group->termine()->sync([]);
            $group->listen()->sync([]);
            $group->media()->delete();
            $group->delete();
        }
    }
}

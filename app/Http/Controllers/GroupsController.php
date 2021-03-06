<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateGroupRequest;
use App\Model\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GroupsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view groups']);
    }

    public function index()
    {
        $groups = Cache::remember('groups', 60 * 5, function () {
            return Group::with('users')->get();
        });

        return view('groups.index')->with([
            'groups'=> $groups,
        ]);
    }

    public function store(CreateGroupRequest $createGroupRequest)
    {
        $group = new Group($createGroupRequest->validated());
        $group->save();

        Cache::forget('groups');
        Cache::remember('groups', 60 * 5, function () {
            return Group::all();
        });

        return redirect()->back()->with([
            'type'  => 'success',
            'Meldung'   => 'Gruppe wurde erstellt',
        ]);
    }
}

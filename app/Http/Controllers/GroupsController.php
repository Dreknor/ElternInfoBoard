<?php

namespace App\Http\Controllers;

use App\Model\Group;
use Illuminate\Http\Request;

class GroupsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view groups']);
    }

    public function index(){
        $groups = Group::with('users')->get();

        return view('groups.index')->with([
            'groups'=> $groups
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\createUserRequest;
use App\Http\Requests\verwaltungEditUserRequest;
use App\Model\Groups;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:edit user']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('user.index', [
            'users' => User::all()->load('groups', 'permissions', 'sorgeberechtigter2')
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('user.create',[
            'gruppen'   => Groups::all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(createUserRequest $request)
    {
        $user = new User($request->all());
        $user->password = Hash::make($request->input('password'));
        $user->changePassword = true;
        $user->lastEmail = Carbon::now();
        $user->save();

        $gruppen= $request->input('gruppen');

        if ($gruppen[0] == "all"){
            $gruppen = Groups::all();
        } elseif ($gruppen[0] == 'Grundschule' or $gruppen[0] == 'Oberschule' ){
            $gruppen = Groups::whereIn('bereich', $gruppen)->orWhereIn('id', $gruppen)->get();
            $gruppen = $gruppen->unique();
        } else {
            $gruppen = Groups::find($gruppen);
        }

        $user->groups()->attach($gruppen);

        return redirect(url("users/$user->id"))->with([
            'type'  => "success",
            "Meldung"   => "Benutzer wurde angelegt"
        ]);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return view('user.show',[
            "user" => $user->load('groups'),
            'gruppen'   => Groups::all(),
            'permissions' => Permission::all()
        ]);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(verwaltungEditUserRequest $request, User $user)
    {
        $user->fill($request->all());
        $gruppen= $request->input('gruppen');

        if ($gruppen[0] == "all"){
            $gruppen = Groups::all();
        } else {
            $gruppen = Groups::find($gruppen);
        }


        $user->groups()->detach();
        $user->groups()->attach($gruppen);

        if (auth()->user()->can('edit permission')){
            $permissions= $request->input('permissions');
            $user->syncPermissions($permissions);
        }


        if ($user->save()){
            return redirect()->back()->with([
               "type"   => "success",
               "Meldung"    => "Daten gespeichert."
            ]);
        }

        return redirect()->back()->with([
        "type"   => "danger",
        "Meldung"    => "Update fehlgeschlagen"
    ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);
        $user->delete();

        return response()->json([
            "message"   => "Gelöscht"
        ], 200);
    }
}

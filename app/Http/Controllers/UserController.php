<?php

namespace App\Http\Controllers;

use App\Http\Requests\createUserRequest;
use App\Http\Requests\verwaltungEditUserRequest;
use App\Model\Groups;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:edit user']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
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
     * @return View
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
     * @return RedirectResponse
     */
    public function store(createUserRequest $request)
    {
        $user = new User($request->all());
        $user->password = Hash::make($request->input('password'));
        $user->changePassword = true;
        $user->lastEmail = Carbon::now();
        $user->save();

        $gruppen= $request->input('gruppen');
        if (isset($gruppen)){
            if ($gruppen[0] == "all"){
                $gruppen = Groups::where('protected', 0)->get();
            } elseif ($gruppen[0] == 'Grundschule' or $gruppen[0] == 'Oberschule' ){
                $gruppen = Groups::whereIn('bereich', $gruppen)->orWhereIn('id', $gruppen)->get();
                $gruppen = $gruppen->unique();
            } else {
                $gruppen = Groups::find($gruppen);
            }

            $user->groups()->attach($gruppen);
        }


        return redirect(url("users/$user->id"))->with([
            'type'  => "success",
            "Meldung"   => "Benutzer wurde angelegt"
        ]);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return View
     */
    public function show(User $user)
    {
        return view('user.show',[
            "user" => $user->load('groups'),
            'gruppen'   => Groups::all(),
            'permissions' => Permission::all(),
            'roles'     => Role::all()
        ]);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return RedirectResponse
     */
    public function update(verwaltungEditUserRequest $request, User $user)
    {
        $user->fill($request->all());
        $gruppen= $request->input('gruppen');

        if (!is_null($gruppen) ){
            if ($gruppen[0] == "all"){
                $gruppen = Groups::all();
            } else {
                $gruppen = Groups::find($gruppen);
            }
        }

        $user->groups()->detach();
        $user->groups()->attach($gruppen);

        if (auth()->user()->can('edit permission')){
            $permissions= $request->input('permissions');
            $user->syncPermissions($permissions);

            $roles= $request->input('roles');
            $user->syncRoles($roles);
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
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $user = User::find($id);
        $user->delete();

        return response()->json([
            "message"   => "Gelöscht"
        ], 200);
    }

    public function loginAsUser($id){
        if (!auth()->user()->hasRole('Admin')){
            return redirect()->back()->with([
               'Meldung'    => "Berechtigung fehlt",
               'type'       => "danger"
            ]);
        }
        session(['ownID' => auth()->user()->id]);

        Auth::loginUsingId($id);

        return redirect(url('/'));

    }

    public function logoutAsUser(){
        if (session()->has('ownID')){
            Auth::loginUsingId(session()->pull('ownID'));
        }
        return redirect(url('/'));
    }
}

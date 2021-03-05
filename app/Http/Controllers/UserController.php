<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\createUserRequest;
use App\Http\Requests\verwaltungEditUserRequest;
use App\Model\Group;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
            'users' => Cache::remember('users_all', 60 * 5, function () {
                return User::all()->load('groups', 'permissions', 'sorgeberechtigter2', 'roles');
            }),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create()
    {
        return view('user.create', [
            'gruppen'   => Cache::remember('groups', 60 * 5, function () {
                return Group::all();
            }),
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

        $gruppen = $request->input('gruppen');
        if (isset($gruppen)) {
            if ($gruppen[0] == 'all') {
                $gruppen = Group::where('protected', 0)->get();
            } elseif ($gruppen[0] == 'Grundschule' or $gruppen[0] == 'Oberschule') {
                $gruppen = Group::whereIn('bereich', $gruppen)->orWhereIn('id', $gruppen)->get();
                $gruppen = $gruppen->unique();
            } else {
                $gruppen = Group::find($gruppen);
            }

            $user->groups()->attach($gruppen);
        }

        return redirect(url("users/$user->id"))->with([
            'type'  => 'success',
            'Meldung'   => 'Benutzer wurde angelegt',
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
        return view('user.show', [
            'user' => $user->load('groups'),
            'gruppen'   => Cache::remember('groups', 60 * 5, function () {
                return Group::all();
            }),
            'permissions' => Cache::remember('permissions', 60 * 5, function () {
                return Permission::all();
            }),
            'roles'     => Cache::remember('role', 60 * 5, function () {
                return Role::all();
            }),
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
        $gruppen = $request->input('gruppen');

        if (! is_null($gruppen)) {
            if ($gruppen[0] == 'all') {
                $gruppen = Group::all();
            } else {
                $gruppen = Group::find($gruppen);
            }
        }

        $user->groups()->detach();
        $user->groups()->attach($gruppen);

        if ($request->user()->can('edit permission')) {
            $permissions = $request->input('permissions');
            $user->syncPermissions($permissions);

            $roles = $request->input('roles');
            $user->syncRoles($roles);
        }

        if ($request->user()->can('set password') and $request->input('new-password') != '') {
            $user->password = Hash::make($request->input('new-password'));
        }

        if ($user->save()) {
            return redirect()->back()->with([
               'type'   => 'success',
               'Meldung'    => 'Daten gespeichert.',
            ]);
        }

        return redirect()->back()->with([
        'type'   => 'danger',
        'Meldung'    => 'Update fehlgeschlagen',
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
        $user->groups()->detach();

        if ($user->sorg2 != null) {
            $sorg2 = User::where('id', '=', $user->sorg2)->first();
            if (! is_null($sorg2)) {
                $sorg2->update([
                        'sorg2'=>null,
                    ]
                );
            }

            $user->update([
                'sorg2'=>null,
            ]);
        }

        //$user->groups()->sync([]);
        $user->listen_eintragungen()->delete();
        $user->userRueckmeldung()->delete();
        $user->reinigung()->delete();

        $user->posts()->update([
            'author'=>null,
        ]);

        $user->delete();

        return response()->json([
            'message'   => 'Gelöscht',
        ], 200);
    }

    public function loginAsUser(Request $request, $id)
    {
        if (! $request->user()->can('loginAsUser')) {
            return redirect()->back()->with([
               'Meldung'    => 'Berechtigung fehlt',
               'type'       => 'danger',
            ]);
        }
        session(['ownID' => $request->user()->id]);

        Auth::loginUsingId($id);

        return redirect()->to(url('/'));
    }

    public function logoutAsUser(Request $request)
    {
        if ($request->session()->has('ownID')) {
            Auth::loginUsingId($request->session()->pull('ownID'));
        }

        return redirect()->to(url('/'));
    }

    public function removeVerknuepfung(User $user)
    {
        $user->sorgeberechtigter2()->update([
            'sorg2' => null,
        ]);

        $user->update([
            'sorg2' => null,
        ]);

        return redirect()->back()->with([
            'type'=>'success',
            'Meldung'   => 'Verknüpfung der Konten aufgehoben',
        ]);
    }
}

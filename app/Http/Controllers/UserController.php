<?php

namespace App\Http\Controllers;

use App\Http\Requests\createUserRequest;
use App\Http\Requests\PasswordlessUserRequest;
use App\Http\Requests\verwaltungEditUserRequest;
use App\Model\Discussion;
use App\Model\Group;
use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Model\listen_termine;
use App\Model\Poll;
use App\Model\Poll_Votes;
use App\Model\Post;
use App\Model\User;
use App\Repositories\GroupsRepository;
use Carbon\Carbon;
use Grosv\LaravelPasswordlessLogin\LoginUrl;
use Grosv\LaravelPasswordlessLogin\PasswordlessLogin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private $groupsRepository;

    public function __construct(GroupsRepository $groupsRepository)
    {
        $this->groupsRepository = $groupsRepository;
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
            'users' => User::all()->load('groups', 'permissions', 'sorgeberechtigter2', 'roles'),

        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create()
    {


        if (auth()->user()->can('edit permission')) {
            $roles = Role::all();
        } elseif (auth()->user()->can('assign roles to users')) {
            $roles = Role::whereHas('permissions', function ($query) {
                $query->where('name', 'role is assignable');
            })->get();
        } else {
            $roles = collect([]);
        }

        return view('user.create', [
            'gruppen' => Cache::remember('groups', 60 * 5, function () {
                return Group::all();
            }),
            'roles' => $roles
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param createUserRequest $request
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
            $user->groups()->sync($gruppen);
        }

        if (auth()->user()->can('edit permission') or auth()->user()->can('assign roles to users')) {
            if (auth()->user()->can('edit permission')) {
                $roles = Role::whereIn('name', $request->roles)->get();
                $roles->unique();
                $user->roles()->attach($roles);
            } elseif (auth()->user()->can('assign roles to users')) {
                if (!is_null($request->roles)) {
                    $roles = Role::whereIn('name', $request->roles)->whereHas('permissions', function ($query) {
                        $query->where('name', 'role is assignable');
                    })->get();
                    $roles->unique();
                    $user->roles()->attach($roles);
                }
            }
        }

        return redirect(url("users/$user->id"))->with([
            'type' => 'success',
            'Meldung' => 'Benutzer wurde angelegt',
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return View
     */
    public function show(User $user)
    {
        if (auth()->user()->can('edit permission')) {
            $roles = Role::all();
        } elseif (auth()->user()->can('assign roles to users')) {
            $roles = Role::whereHas('permissions', function ($query) {
                $query->where('name', 'role is assignable');
            })->get();
        } else {
            $roles = collect([]);
        }

        return view('user.show', [
            'user' => $user->load('groups'),
            'gruppen' => Cache::remember('groups', 60 * 5, function () {
                return Group::all();
            }),
            'permissions' => Cache::remember('permissions', 60 * 5, function () {
                return Permission::all();
            }),
            'roles' => $roles,
            'users' => User::where([
                ['sorg2', null],
                ['id', '!=', $user->id],
            ])->orWhere('sorg2', $user->id)->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param verwaltungEditUserRequest $request
     * @param User $user
     * @return RedirectResponse
     */
    public function update(verwaltungEditUserRequest $request, User $user)
    {
        $user->fill($request->validated());
        $gruppen = $request->input('gruppen');

        if (!is_null($gruppen)) {
            $gruppen = $this->groupsRepository->getGroups($gruppen);
            $user->groups()->detach();
            $user->groups()->attach($gruppen);
        }


        if ($request->user()->can('edit permission')) {
            $permissions = $request->input('permissions');
            $user->syncPermissions($permissions);
        }

        if (!is_null($request->roles)) {
            if (auth()->user()->can('edit permission') or auth()->user()->can('assign roles to users')) {
                if (auth()->user()->can('edit permission') and !is_null($request->roles)) {
                    $roles = Role::whereIn('name', $request->roles)->get();
                    $roles->unique();
                    $user->roles()->sync($roles);
                } elseif (auth()->user()->can('assign roles to users') and !is_null($request->roles)) {
                    $roles = Role::whereIn('name', $request->roles)->whereHas('permissions', function ($query) {
                        $query->where('name', 'role is assignable');
                    })->get();
                    $roles->unique();

                    $old_roles = $user->roles()->whereDoesntHave('permissions', function ($query) {
                        $query->where('name', 'role is assignable');
                    })->get();

                    $user->roles()->sync($roles);
                    $user->roles()->attach($old_roles);
                }
            }
        }


        if ($request->user()->can('set password') and $request->input('new-password') != '') {
            $user->password = Hash::make($request->input('new-password'));
        }

        if ($request->sorg2 != '') {
            User::where('id', $request->sorg2)->update([
                'sorg2' => $user->id,
            ]);
        }

        if ($user->save()) {
            return redirect()->back()->with([
                'type' => 'success',
                'Meldung' => 'Daten gespeichert.',
            ]);
        }

        return redirect()->back()->with([
            'type' => 'danger',
            'Meldung' => 'Update fehlgeschlagen',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy(int $id, $redirect = true)
    {
        try {
            $user = User::findOrFail($id);
            $user->groups()->detach();

            if ($user->sorg2 != null) {
                $sorg2 = User::where('id', '=', $user->sorg2)->first();
                if (!is_null($sorg2)) {
                    $sorg2->update([
                            'sorg2' => null,
                        ]
                    );
                }

                $user->update([
                    'sorg2' => null,
                ]);
            }

            $user->schickzeiten()->where('users_id', $user->id)->forceDelete();

            Listen_Eintragungen::where('created_by', $user->id)->delete();
            Listen_Eintragungen::where('user_id', $user->id)->update(['user_id' => null]);
            Discussion::where('owner', $user->id)->update(['owner' => null]);
            listen_termine::where('reserviert_fuer', $user->id)->delete();
            Poll::where('author_id', $user->id)->update(['author_id' => null]);
            Poll_Votes::where('author_id', $user->id)->delete();

            Liste::query()->where('besitzer', $user->id)->update(['besitzer' => null]);


            $user->listen_termine()->delete();
            $user->userRueckmeldung()->delete();
            $user->reinigung()->delete();

            $user->schickzeiten_own()->delete();
            $user->krankmeldungen()->withTrashed()->forceDelete();
            $user->comments()->delete();

            Post::query()->where('author', $user->id)->update(['author' => null]);

            $user->delete();

            $Fehler = "";
        } catch (\Exception $exception) {
            $Fehler = $exception;
        }

        if ($redirect) {
            return redirect()->back()->with([
                'type' => ($Fehler == "") ? 'success' : 'danger',
                'Meldung' => ($Fehler != "") ? $Fehler : 'Benutzer gelöscht',
            ]);
        } else {
            return $Fehler;
        }

    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function loginAsUser(Request $request, $id)
    {
        if (! $request->user()->can('loginAsUser')) {
            return redirect()->back()->with([
                'Meldung' => 'Berechtigung fehlt',
                'type' => 'danger',
            ]);
        }
        session(['ownID' => Crypt::encryptString($request->user()->id)]);

        Auth::loginUsingId($id);

        return redirect()->to(url('/'));
    }


    /**
     * @param User $user
     * @param Int $sorg2
     * @return RedirectResponse
     */
    public function removeVerknuepfung(User $user, Int $sorg2)
    {
        if ($user->sorg2 != $sorg2){
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Verknüpfung konnte nicht aufgehoben werden, da User und Sorgeberechtigter nicht übereinstimmen.',
            ]);
        }

        $user->sorgeberechtigter2()->update([
            'sorg2' => null,
        ]);

        $user->update([
            'sorg2' => null,
        ]);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Verknüpfung der Konten aufgehoben',
        ]);
    }

    public function showMassDelete()
    {


        $users = User::query()->whereHas('roles', function ($query) {
            return $query->where('name', 'Eltern');
        })->doesntHave('groups')->get();

        return view('user.showMassDelete')->with([
            'users' => $users
        ]);
    }

    public function massDelete(Request $request)
    {

        if ($request->users) {
            $fehler = "";

            foreach ($request->users as $user) {
                $ergebnis = $this->destroy($user, false);
                if ($ergebnis != "") {
                    if ($fehler) {
                        $fehler .= ', ' . $user;
                    } else {
                        $fehler = 'Fehler bei folgenden Benutzern: ' . $user;
                    }
                }
            }

            return redirect()->back()->with([
                'type' => ($fehler == "") ? 'success' : 'danger',
                'Meldung' => ($fehler != "") ? $fehler : 'Benutzer gelöscht',
            ]);
        }

        return redirect(url('users'))->with([
            'type' => 'warning',
            'Meldung' => "Keine Benutzer zum Löschen ausgewählt"
        ]);
    }

}

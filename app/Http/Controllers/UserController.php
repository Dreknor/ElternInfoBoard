<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Mail\NewUserPasswordMail;
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
use App\Services\UserService;
use App\Settings\EmailSetting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller implements HasMiddleware
{
    private $groupsRepository;

    private UserService $userService;

    public function __construct(GroupsRepository $groupsRepository, UserService $userService)
    {
        $this->groupsRepository = $groupsRepository;
        $this->userService = $userService;
    }

    public static function middleware(): array
    {
        return [
            ['permission:edit user'],
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(Request $request)
    {
        // TODO-2.7: Serverseitige Paginierung statt User::all() für bessere Performance
        $query = User::query()->with(['groups', 'permissions', 'sorgeberechtigter2', 'roles']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        if ($group = $request->input('group')) {
            $query->whereHas('groups', fn ($q) => $q->where('groups.id', $group));
        }

        return view('user.index', [
            'users' => $query->orderBy('name')->paginate(50)->withQueryString(),
            'roles' => Role::all(),
            // TODO-2.7: WICHTIG – GetGroupsScope umgehen damit Admin alle Gruppen im Filter sieht
            'groups' => Group::withoutGlobalScope(\App\Scopes\GetGroupsScope::class)->orderBy('name')->get(),
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
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     */
    public function store(CreateUserRequest $request)
    {
        // TODO-2.2: Erstellung über UserService delegieren
        $result = $this->userService->createUser($request->safe()->only(['name', 'email']));
        $user = $result['user'];

        $this->userService->syncGroups($user, $request->input('gruppen'));
        $this->userService->syncRoles($user, $request->input('roles'), $request->user());

        return redirect(url("users/$user->id"))->with([
            'type' => 'success',
            'Meldung' => 'Benutzer wurde angelegt. '.$result['emailStatus'],
        ]);
    }

    /**
     * Display the specified resource.
     *
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
     * @return RedirectResponse
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        // TODO-2.2: Update über UserService delegieren
        $this->userService->updateUser($user, $request->validated());
        $this->userService->syncGroups($user, $request->input('gruppen'));

        if ($request->user()->can('edit permission')) {
            $this->userService->syncPermissions($user, $request->input('permissions'));
        }

        if (! is_null($request->roles)) {
            $this->userService->syncRoles($user, $request->input('roles'), $request->user());
        }

        if ($request->user()->can('set password') && $request->filled('new-password')) {
            $this->userService->setPassword($user, $request->input('new-password'));
        }

        if ($request->filled('sorg2')) {
            $this->userService->linkSorgeberechtigte($user, (int) $request->input('sorg2'));
        }

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Daten gespeichert.',
        ]);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @return RedirectResponse
     */
    public function destroy(int $id, $redirect = true)
    {
        $user = User::find($id);
        $Fehler = $user ? $this->userService->deleteUser($user) : 'Benutzer nicht gefunden.';

        if ($redirect) {
            return redirect()->back()->with([
                'type' => ($Fehler == '') ? 'success' : 'danger',
                'Meldung' => ($Fehler != '') ? $Fehler : 'Benutzer gelöscht',
            ]);
        } else {
            return $Fehler;
        }

    }

    /**
     * @return RedirectResponse
     */
    public function loginAsUser(Request $request, $id)
    {
        if (! $request->user()->can('loginAsUser')) {
            Log::warning('loginAsUser: Zugriff verweigert (Berechtigung fehlt)', [
                'requestor_id' => $request->user()->id,
                'requestor_email' => $request->user()->email,
                'target_user_id' => $id,
                'ip' => $request->ip(),
            ]);
            return redirect()->back()->with([
                'Meldung' => 'Berechtigung fehlt',
                'type' => 'danger',
            ]);
        }

        $targetUser = User::findOrFail($id);

        Log::warning('Impersonation gestartet', [
            'admin_id' => $request->user()->id,
            'admin_name' => $request->user()->name,
            'target_id' => $targetUser->id,
            'target_name' => $targetUser->name,
            'ip' => $request->ip(),
        ]);

        session(['ownID' => Crypt::encryptString($request->user()->id)]);

        Auth::loginUsingId($id);

        return redirect()->to(url('/'));
    }

    /**
     * @return RedirectResponse
     */
    public function removeVerknuepfung(User $user, int $sorg2)
    {
        if ($user->sorg2 != $sorg2) {
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
        $users = User::query()
            ->whereHas('roles', function ($query) {
                return $query->where('name', 'Eltern');
            })
            ->with(['groups', 'roles', 'permissions', 'sorgeberechtigter2'])
            ->get();

        return view('user.showMassDelete')->with([
            'users' => $users,
        ]);
    }

    public function massDelete(Request $request)
    {
        // TODO-2.2: Massenlöschung über UserService delegieren
        $userIds = $request->input('user_ids', []);
        $result = $this->userService->massDeleteUsers($userIds);

        return redirect()->back()->with([
            'type' => ($result['errors'] == '') ? 'success' : 'danger',
            'Meldung' => ($result['deleted'] ? "{$result['deleted']} Benutzer gelöscht. " : '').$result['errors'],
        ]);
    }

    /**
     * Zeigt User, die nicht in der Gruppe Vereinsmitglied sind
     *
     * @return View
     */
    public function showNonVereinsmitglieder()
    {
        $vereinsgruppe = Group::where('name', 'Vereinsmitglied')->first();

        // Alle User, die weder selbst noch deren Sorg2 in der Gruppe Vereinsmitglied sind
        $users = User::whereDoesntHave('groups', function ($query) use ($vereinsgruppe) {
            $query->where('groups.id', $vereinsgruppe?->id);
        })
            ->where(function ($query) use ($vereinsgruppe) {
                // User ohne Sorg2 ODER User deren Sorg2 nicht in der Gruppe ist
                $query->whereNull('sorg2')
                    ->orWhereDoesntHave('sorgeberechtigter2.groups', function ($q) use ($vereinsgruppe) {
                        $q->where('groups.id', $vereinsgruppe?->id);
                    });
            })
            ->with(['groups', 'roles', 'permissions', 'sorgeberechtigter2'])
            ->get();

        // Bereite die User-Daten für Alpine.js vor
        $usersData = $users->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'groups' => $u->groups->pluck('name')->toArray(),
                'roles' => $u->roles->pluck('name')->toArray(),
                'sorg2' => $u->sorgeberechtigter2?->name,
                'removed' => false,
            ];
        })->values();

        // Alle verfügbaren Rollen für den Filter
        $allRoles = Role::all();

        return view('user.showNonVereinsmitglieder', [
            'users' => $users,
            'usersData' => $usersData,
            'roles' => $allRoles,
            'vereinsgruppe' => $vereinsgruppe,
        ]);
    }

    /**
     * Fügt einen User zur Gruppe Vereinsmitglied hinzu (Ajax)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToVereinsmitglied(Request $request)
    {
        $userId = $request->input('user_id');
        $user = User::find($userId);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Benutzer nicht gefunden',
            ], 404);
        }

        $vereinsgruppe = Group::where('name', 'Vereinsmitglied')->first();

        if (! $vereinsgruppe) {
            return response()->json([
                'success' => false,
                'message' => 'Gruppe Vereinsmitglied nicht gefunden',
            ], 404);
        }

        // Zur Gruppe hinzufügen
        $user->groups()->syncWithoutDetaching([$vereinsgruppe->id]);

        return response()->json([
            'success' => true,
            'message' => 'Benutzer zur Gruppe Vereinsmitglied hinzugefügt',
        ]);
    }

    /**
     * Überprüft alle Vereinsmitglieder und fügt ihnen die Rolle hinzu falls nicht vorhanden
     *
     * @return RedirectResponse
     */
    public function syncVereinsmitgliederRole()
    {
        $vereinsgruppe = Group::where('name', 'Vereinsmitglied')->first();
        $vereinsrolle = Role::where('name', 'Vereinsmitglied')->first();

        if (! $vereinsgruppe || ! $vereinsrolle) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Gruppe oder Rolle Vereinsmitglied nicht gefunden',
            ]);
        }

        $users = $vereinsgruppe->users;
        $updated = 0;

        foreach ($users as $user) {
            if (! $user->hasRole('Vereinsmitglied')) {
                $user->assignRole('Vereinsmitglied');
                $updated++;
            }
        }

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => "$updated Benutzer(n) wurde die Rolle Vereinsmitglied zugewiesen",
        ]);
    }
}

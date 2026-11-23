<?php

namespace App\Http\Controllers;

use App\Model\Module;
use App\Model\User;
use App\Settings\GeneralSetting;
use App\Services\Security\SecurityLog;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesController extends Controller
{
    /**
     * Rollen, deren Namen im Code fest verwendet werden (findByName, hasRole, assignRole …).
     * Sie dürfen nicht gelöscht werden, sonst brechen Funktionen.
     */
    public const SYSTEM_ROLES = ['Administrator', 'Mitarbeiter', 'Eltern', 'Elternrat', 'Aufnahme'];

    /**
     * @return Application|Factory|View
     */
    public function edit()
    {
        $permissionsByModule = Permission::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', Module::lockedPermissionNames())
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy('module');

        $rollen = Role::query()->with('permissions')->withCount('users')->orderBy('name')->get();

        return view('permissions.edit', [
            'Rollen' => $rollen,
            'permissionsByModule' => $permissionsByModule,
            'rollenVerwaltung' => $this->deletionOverview($rollen),
        ]);
    }

    /**
     * Daten für die Karte „Rollen verwalten“ und den Bestätigungsdialog.
     */
    private function deletionOverview($rollen): array
    {
        $deletable = $rollen->reject(fn (Role $role) => $this->isSystemRole($role));

        $usersByRole = DB::table('model_has_roles')
            ->join('users', 'users.id', '=', 'model_has_roles.model_id')
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->whereIn('model_has_roles.role_id', $deletable->pluck('id'))
            ->orderBy('users.name')
            ->get(['model_has_roles.role_id', 'users.id', 'users.name'])
            ->groupBy('role_id');

        $roleCountByUser = DB::table('model_has_roles')
            ->where('model_type', (new User)->getMorphClass())
            ->whereIn('model_id', $usersByRole->flatten()->pluck('id')->unique())
            ->groupBy('model_id')
            ->pluck(DB::raw('count(*)'), 'model_id');

        return $rollen->map(function (Role $role) use ($usersByRole, $roleCountByUser) {
            $users = $usersByRole->get($role->id, collect());

            return [
                'id' => $role->id,
                'name' => $role->name,
                'locked' => $this->isSystemRole($role),
                'users_count' => $role->users_count,
                'users' => $users->pluck('name')->values()->all(),
                'users_without_other_role' => $users->filter(fn ($u) => (int) ($roleCountByUser[$u->id] ?? 0) <= 1)->count(),
                'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
            ];
        })->values()->all();
    }

    private function isSystemRole(Role $role): bool
    {
        return in_array($role->name, self::SYSTEM_ROLES, true);
    }

    /**
     * Löscht eine Rolle endgültig. Zuweisungen an Benutzer werden entfernt,
     * bei Rollen mit Benutzern muss der Rollenname zur Bestätigung eingegeben werden.
     */
    public function destroy(Request $request, Role $role): RedirectResponse
    {
        if ($this->isSystemRole($role)) {
            return $this->fail("Die Rolle „{$role->name}“ ist eine Systemrolle und kann nicht gelöscht werden.");
        }

        $userIds = $role->users()->pluck('users.id')->all();

        if ($userIds !== [] && trim((string) $request->input('confirm_name')) !== $role->name) {
            return $this->fail('Zum Löschen einer Rolle mit Benutzern muss der Rollenname exakt eingegeben werden.');
        }

        /** @var User $actor */
        $actor = $request->user();
        if ($this->wouldLockOut($actor, $role)) {
            return $this->fail("Die Rolle „{$role->name}“ kann nicht gelöscht werden, weil Sie dadurch selbst die Rechteverwaltung verlieren würden.");
        }

        $roleId = $role->id;
        $roleName = $role->name;
        $permissions = $role->permissions->pluck('name')->sort()->values()->all();
        $rolesBefore = User::query()->whereIn('id', $userIds)->with('roles')->get()
            ->mapWithKeys(fn (User $u) => [$u->id => $u->roles->pluck('name')->sort()->values()->all()]);

        DB::transaction(function () use ($role, $roleName) {
            $role->delete();

            $settings = app(GeneralSetting::class);
            if (in_array($roleName, $settings->protected_roles, true)) {
                $settings->protected_roles = array_values(array_diff($settings->protected_roles, [$roleName]));
                $settings->save();
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        SecurityLog::record('rbac.role_changed', null, [
            'action' => 'role_deleted',
            'role_id' => $roleId,
            'role' => $roleName,
            'permissions' => $permissions,
            'permission_count' => count($permissions),
            'user_count' => count($userIds),
        ]);

        // Pro Konto ein Eintrag (wie bei Rollenänderungen am Konto), damit sich
        // Zuweisungen auch bei vielen Benutzern vollständig wiederherstellen lassen.
        foreach ($rolesBefore as $userId => $before) {
            SecurityLog::record('rbac.user_roles_changed', $userId, [
                'before' => $before,
                'after' => array_values(array_diff($before, [$roleName])),
                'reason' => 'role_deleted',
            ]);
        }

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => "Rolle „{$roleName}“ gelöscht".($userIds !== [] ? ' ('.count($userIds).' Benutzer betroffen)' : ''),
        ]);
    }

    /**
     * Verliert der handelnde Benutzer durch das Löschen das Recht zur Rechteverwaltung?
     */
    private function wouldLockOut(User $actor, Role $role): bool
    {
        if (! $actor->hasRole($role) || ! $role->hasPermissionTo('edit permission')) {
            return false;
        }

        if ($actor->getDirectPermissions()->contains('name', 'edit permission')) {
            return false;
        }

        return ! $actor->roles
            ->reject(fn (Role $r) => $r->id === $role->id)
            ->contains(fn (Role $r) => $r->hasPermissionTo('edit permission'));
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->back()->with([
            'type' => 'danger',
            'Meldung' => $message,
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function update(Request $request)
    {
        $hidden = Module::lockedPermissionNames();

        foreach (Role::all() as $role) {
            // Rechte gesperrter Module stehen nicht im Formular und bleiben unverändert.
            $before = $role->permissions->pluck('name')->sort()->values()->all();
            $keep = $role->permissions->pluck('name')->intersect($hidden)->all();
            $role->syncPermissions(array_merge((array) $request->input($role->name, []), $keep));

            $after = $role->load('permissions')->permissions->pluck('name')->sort()->values()->all();
            if ($before !== $after) {
                SecurityLog::record('rbac.role_changed', null, [
                    'action' => 'permissions_synced',
                    'role_id' => $role->id,
                    'role' => $role->name,
                    'added' => array_values(array_diff($after, $before)),
                    'removed' => array_values(array_diff($before, $after)),
                ]);
            }
        }

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Berechtigungen gespeichert',
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $role = Role::firstOrCreate(['name' => $request->name]);
        if ($role->wasRecentlyCreated) {
            SecurityLog::record('rbac.role_changed', null, ['action' => 'role_created', 'role_id' => $role->id, 'role' => $role->name]);
        }

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Rolle erstellt',
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function storePermission(Request $request)
    {
        $permission = Permission::firstOrCreate(['name' => $request->name]);
        if ($permission->wasRecentlyCreated) {
            SecurityLog::record('rbac.role_changed', null, ['action' => 'permission_created', 'permission_id' => $permission->id, 'permission' => $permission->name]);
        }

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Berechtigung erstellt',
        ]);
    }
}

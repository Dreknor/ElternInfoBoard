<?php

namespace App\Http\Controllers;

use App\Model\Module;
use App\Services\Security\SecurityLog;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
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

        return view('permissions.edit', [
            'Rollen' => Role::all(),
            'permissionsByModule' => $permissionsByModule,
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

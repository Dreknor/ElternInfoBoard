<?php

namespace App\Http\Controllers;

use App\Model\Module;
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
            $keep = $role->permissions->pluck('name')->intersect($hidden)->all();
            $role->syncPermissions(array_merge((array) $request->input($role->name, []), $keep));
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
        Role::firstOrCreate(['name' => $request->name]);

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
        Permission::firstOrCreate(['name' => $request->name]);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Berechtigung erstellt',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit()
    {
        return view('permissions.edit', [
            'Rollen' => Role::all(),
            'Rechte' => Permission::all(),
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        foreach (Role::all() as $role) {
            $role->syncPermissions($request->input($role->name));
        }

        return  redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Berechtigungen gespeichert',
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $Role = Role::firstOrCreate(['name' => $request->name]);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Rolle erstellt',
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storePermission(Request $request)
    {
        $Role = Permission::firstOrCreate(['name' => $request->name]);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Berechtigung erstellt',
        ]);
    }
}

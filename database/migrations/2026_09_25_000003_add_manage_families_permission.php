<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission zum Pflegen von Familien und Kind-Beziehungen (nur Admin, E6).
 * Wird allen Rollen gegeben, die heute Benutzer bearbeiten dürfen.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::findOrCreate('manage families', 'web');

        Role::query()
            ->where('guard_name', 'web')
            ->whereHas('permissions', fn ($q) => $q->where('name', 'edit user'))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'manage families')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

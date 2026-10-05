<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Legt die Permission "manage attendance queries" (Anwesenheitsabfragen erstellen,
 * Kinder nachtragen und löschen) für bestehende Installationen an. Damit sich für
 * bestehende Installationen nichts ändert, erhalten alle Rollen, die bereits
 * "edit schickzeiten" besitzen, sowie die Administrator-Rolle das neue Recht.
 * Über die Rollenverwaltung kann es anschließend gezielt entzogen werden.
 */
return new class extends Migration
{
    private const PERMISSION_NAME = 'manage attendance queries';

    public function up(): void
    {
        try {
            $permission = Permission::firstOrCreate(
                ['name' => self::PERMISSION_NAME, 'guard_name' => 'web'],
            );

            $permission->module = 'Care';
            $permission->description = 'Anwesenheitsabfragen erstellen, Kinder nachtragen und Abfragen löschen.';
            $permission->save();

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            Role::query()
                ->whereHas('permissions', fn ($query) => $query->where('name', 'edit schickzeiten'))
                ->get()
                ->each(fn (Role $role) => $role->givePermissionTo($permission));

            $adminRole = Role::where('name', 'Administrator')->first();
            $adminRole?->givePermissionTo($permission);

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Exception $e) {
            Log::error('Failed to create "manage attendance queries" permission: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        Permission::where('name', self::PERMISSION_NAME)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

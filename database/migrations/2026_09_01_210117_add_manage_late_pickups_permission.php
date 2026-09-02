<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Legt die Permission "manage late pickups" für bestehende Installationen an,
 * unabhängig davon, ob die Seeder erneut ausgeführt werden. Rollen, die
 * bereits "edit schickzeiten" besitzen (z. B. Hort, Sekretariat), sowie die
 * Administrator-Rolle erhalten das neue Recht automatisch.
 */
return new class extends Migration
{
    private const PERMISSION_NAME = 'manage late pickups';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            $permission = Permission::firstOrCreate(
                ['name' => self::PERMISSION_NAME, 'guard_name' => 'web'],
            );

            $permission->module = 'Care';
            $permission->description = 'Verspätete Abholungen bestätigen oder verwerfen.';
            $permission->save();

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Rollen, die bereits Schickzeiten verwalten dürfen, erhalten das neue Recht.
            Role::query()
                ->whereHas('permissions', fn ($query) => $query->where('name', 'edit schickzeiten'))
                ->get()
                ->each(fn (Role $role) => $role->givePermissionTo($permission));

            // Sicherstellen, dass die Administrator-Rolle (falls vorhanden) das Recht ebenfalls erhält.
            $adminRole = Role::where('name', 'Administrator')->first();
            $adminRole?->givePermissionTo($permission);

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Exception $e) {
            Log::error('Failed to create "manage late pickups" permission: '.$e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', self::PERMISSION_NAME)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

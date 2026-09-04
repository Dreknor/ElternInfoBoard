<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Legt die Permission "create krankmeldung express" für die Schnellerfassung
 * von Krankmeldungen durch das Sekretariat an. Rollen, die bereits
 * "download krankmeldungen" besitzen (z. B. Sekretariat), sowie die
 * Administrator-Rolle erhalten das neue Recht automatisch.
 */
return new class extends Migration
{
    private const PERMISSION_NAME = 'create krankmeldung express';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            $permission = Permission::firstOrCreate(
                ['name' => self::PERMISSION_NAME, 'guard_name' => 'web'],
            );

            $permission->module = 'Krankmeldungen';
            $permission->description = 'Express-Krankmeldungen für Kinder erfassen (Sekretariat-Schnellerfassung).';
            $permission->save();

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Rollen, die bereits Krankmeldungen herunterladen dürfen, erhalten das neue Recht.
            Role::query()
                ->whereHas('permissions', fn ($query) => $query->where('name', 'download krankmeldungen'))
                ->get()
                ->each(fn (Role $role) => $role->givePermissionTo($permission));

            // Sicherstellen, dass die Administrator-Rolle (falls vorhanden) das Recht ebenfalls erhält.
            $adminRole = Role::where('name', 'Administrator')->first();
            $adminRole?->givePermissionTo($permission);

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Exception $e) {
            Log::error('Failed to create "create krankmeldung express" permission: '.$e->getMessage());
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

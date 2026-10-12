<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Legt die Permission "manage updates" für den Online-Updater an und weist sie
 * der Administrator-Rolle zu.
 */
return new class extends Migration
{
    private const PERMISSION_NAME = 'manage updates';

    public function up(): void
    {
        try {
            $permission = Permission::firstOrCreate(
                ['name' => self::PERMISSION_NAME, 'guard_name' => 'web'],
            );

            $permission->module = 'Einstellungen';
            $permission->description = 'Online-Updater: neue Versionen prüfen und Updates einspielen.';
            $permission->save();

            $adminRole = Role::where('name', 'Administrator')->where('guard_name', 'web')->first();
            if ($adminRole && ! $adminRole->hasPermissionTo($permission)) {
                $adminRole->givePermissionTo($permission);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Exception $e) {
            Log::error('Failed to create "manage updates" permission: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        Permission::where('name', self::PERMISSION_NAME)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

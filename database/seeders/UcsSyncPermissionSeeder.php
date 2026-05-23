<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Fügt die Permission "manage ucs sync" hinzu und weist sie der
 * Administrator-Rolle zu.
 *
 * Ausführen mit:
 *   php artisan db:seed --class=UcsSyncPermissionSeeder
 *
 * Zugewiesene Rollen (Standard): Administrator
 */
class UcsSyncPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Permission anlegen (idempotent via firstOrCreate)
        $permission = Permission::firstOrCreate([
            'name'       => 'manage ucs sync',
            'guard_name' => 'web',
        ]);

        // Permission der Administrator-Rolle zuweisen
        $adminRole = Role::where('name', 'Administrator')
            ->where('guard_name', 'web')
            ->first();

        if ($adminRole && ! $adminRole->hasPermissionTo($permission)) {
            $adminRole->givePermissionTo($permission);
        }

        // Spatie Permission-Cache leeren
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permission "manage ucs sync" wurde angelegt und der Administrator-Rolle zugewiesen.');
    }
}


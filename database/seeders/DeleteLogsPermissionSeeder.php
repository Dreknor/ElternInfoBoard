<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DeleteLogsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the "delete logs" permission
        $permission = Permission::firstOrCreate(['name' => 'delete logs']);

        echo "✓ Permission 'delete logs' erstellt/existiert bereits.\n";

        // Assign to admin role if it exists
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            if (! $adminRole->hasPermissionTo($permission)) {
                $adminRole->givePermissionTo($permission);
                echo "✓ Permission der Admin-Rolle zugewiesen.\n";
            } else {
                echo "ℹ Admin-Rolle hat bereits die Permission.\n";
            }
        }
    }
}

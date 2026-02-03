<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Create the "delete logs" permission
            $permission = Permission::create(['name' => 'delete logs']);

            // Assign to admin role if it exists
            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) {
                $adminRole->givePermissionTo($permission);
            }
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            \Illuminate\Support\Facades\Log::error('Failed to create delete logs permission: '.$e->getMessage());
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'delete logs')->delete();
    }
};

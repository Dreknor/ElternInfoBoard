<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        try {
            $permission = Permission::firstOrCreate(['name' => 'see security log']);

            // Nur Ansicht – bewusst keine Lösch-Permission, das Protokoll ist über die Oberfläche nicht löschbar
            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) {
                $adminRole->givePermissionTo($permission);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create see security log permission: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        Permission::where('name', 'see security log')->delete();
    }
};

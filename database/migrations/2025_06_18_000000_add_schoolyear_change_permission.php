<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        if (! Permission::where('name', 'schoolyear.change')->exists()) {
            Permission::create([
                'name' => 'schoolyear.change',
                'guard_name' => 'web',
            ]);
        }
    }

    public function down(): void
    {
        Permission::where('name', 'schoolyear.change')->delete();
    }
};

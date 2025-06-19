<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        if (!Permission::where('name', 'schoolyear.change')->exists()) {
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


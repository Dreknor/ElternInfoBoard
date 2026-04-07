<?php

use Database\Seeders\CreateStundenplanModuleSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Run the CreateStundenplanModuleSeeder
        $seeder = new CreateStundenplanModuleSeeder();
        $seeder->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove stundenplan permissions
        \Spatie\Permission\Models\Permission::whereIn('name', [
            'view stundenplan',
            'edit stundenplan',
        ])->delete();

        // Remove stundenplan module
        \App\Model\Module::where('setting', 'Stundenplan')->delete();
    }
};


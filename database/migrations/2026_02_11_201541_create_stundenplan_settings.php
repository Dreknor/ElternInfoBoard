<?php

use Database\Seeders\StundenplanSettingsSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Run the StundenplanSettingsSeeder
        $seeder = new StundenplanSettingsSeeder();
        $seeder->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove stundenplan settings
        \Illuminate\Support\Facades\DB::table('settings')
            ->where('group', 'stundenplan')
            ->delete();
    }
};


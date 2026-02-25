<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add the new setting to the settings table
        $exists = DB::table('settings')
            ->where('group', 'stundenplan')
            ->where('name', 'show_absent_teachers')
            ->exists();

        if (!$exists) {
            DB::table('settings')->insert([
                'group' => 'stundenplan',
                'name' => 'show_absent_teachers',
                'locked' => 0,
                'payload' => json_encode(true),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the setting
        DB::table('settings')
            ->where('group', 'stundenplan')
            ->where('name', 'show_absent_teachers')
            ->delete();
    }
};


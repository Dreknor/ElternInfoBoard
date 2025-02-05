<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        DB::table('permissions')->insert([
            'name' => 'view vertretungsplan',
            'guard_name' => 'web',
        ]);

        DB::table('permissions')->insert([
            'name' => 'view vertretungsplan all',
            'guard_name' => 'web',
        ]);

        DB::table('settings_modules')->insert([
            'setting' => 'Vertretungsplan',
            'category' => 'module',
            'options' => '
            {
                "active":"0",
                "rights":{"0":"view vertretungsplan"},
                "nav":
                    {
                        "name":"Vertretungsplan",
                        "link":"vertretungsplan",
                        "icon":"fas fa-columns"
                    }
            }',
            'created_at' => Carbon::now(),
        ]);

        Artisan::call('cache:clear');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
    }
};

<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class AddVertretungsplanSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
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
}

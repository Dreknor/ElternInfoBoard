<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Illuminate\Support\Facades\DB::table('permissions')->insert([
            'name' => 'view vertretungsplan',
            'guard_name' => 'web',
        ]);

        \Illuminate\Support\Facades\DB::table('permissions')->insert([
            'name' => 'view vertretungsplan all',
            'guard_name' => 'web',
        ]);

        \Illuminate\Support\Facades\DB::table('settings')->insert([
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
            'created_at' => \Carbon\Carbon::now(),
        ]);

        \Illuminate\Support\Facades\Artisan::call('cache:clear');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};

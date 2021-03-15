<?php

use Illuminate\Database\Migrations\Migration;


class InsertNewVertretungsplanModuleToModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Illuminate\Support\Facades\DB::table('permissions')->insert([
            'name'  => 'view Vertretungsplan',
            'guard_name'    => 'web',
        ]);

        \Illuminate\Support\Facades\DB::table('settings')->insert([
            'setting'=> 'Vertretungsplan',
            'category'=> 'module',
            'options'=> '
            {
                "active":"0",
                "rights":["view vertretungsplan"],
                "nav":
                    {
                        "name":"Vertretungsplan",
                        "link":"vertretungsplan",
                        "icon":"fas fa-columns"
                    },
                "adm-nav":
                    {
                    }
            }',
            'created_at'=> \Carbon\Carbon::now()
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
}

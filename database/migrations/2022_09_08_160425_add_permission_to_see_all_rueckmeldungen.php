<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPermissionToSeeAllRueckmeldungen extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('permissions')->insert([
            'name' => 'manage rueckmeldungen',
            'guard_name' => 'web',
        ]);

        \Illuminate\Support\Facades\DB::table('settings')->insert([
            'setting' => 'bearbeite Rueckmeldungen',
            'category' => 'module',
            'options' => '
            {
                "active":"0",
                "rights":{},
                "adm-nav":
                    {"adm-rights":
                        ["manage rueckmeldungen"],
                        "name":"Rückmeldungen",
                        "link":"rueckmeldungen",
                        "icon":"fas fa-comment-dots"
                    }
            }',
            'created_at' => \Carbon\Carbon::now()
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
        Schema::table('see_all_rueckmeldungen', function (Blueprint $table) {
            //
        });
    }
}

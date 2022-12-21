<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            'name' => 'manage rueckmeldungen',
            'guard_name' => 'web',
        ]);

        DB::table('settings')->insert([
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
        Schema::table('see_all_rueckmeldungen', function (Blueprint $table) {
            //
        });
    }
};

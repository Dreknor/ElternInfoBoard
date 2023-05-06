<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Spatie\Permission\Models\Permission::firstOrCreate([
           'name' => "view external offer",
            'guard_name' => 'web'
        ]);

        $settings =  [
            'id' => 1150,
            'setting' => 'externe Angebote',
            'description' => 'Nachrichten können als externes Angebot gekennzeichnet werden. Diese erhalten einen eigenen Bereich ähnlich dem Archiv.',
            'category' => 'module',
            'options' => '
                {
                    "active":"1",
                    "rights":{"0":"view external offer"},
                    "nav":
                    {
                        "name":"ex. Angebot",
                        "link":"external",
                        "icon":"fas  fa-info"
                    }
                }',
        ];

        DB::table('settings')->insert($settings);


        exec('php artisan cache:clear');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};

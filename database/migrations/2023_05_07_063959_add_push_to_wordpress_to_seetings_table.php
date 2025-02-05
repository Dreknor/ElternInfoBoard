<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        $settings =  [
            'setting' => 'Push to WordPress',
            'description' => 'Posts können WordPress-Seite geschickt werden. Bedingung ist das Ausfüllen der Daten in der .env-Datei sowie die Vergabe des entsprechenden Rechtes.',
            'category' => 'setting',
            'options' => '{"active":"0"}',
        ];

        DB::table('settings_modules')->insert($settings);
        \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => "push to wordpress",
            'guard_name' => 'web'
        ]);


        exec('php artisan cache:clear');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wordpree_to_seetings', function (Blueprint $table) {
            //
        });
    }
};

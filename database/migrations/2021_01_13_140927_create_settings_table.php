<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('setting');
            $table->text('description')->nullable();
            $table->string('category');
            $table->json('options');
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::table('permissions')->insert(
            array(
                [
                'name' => 'edit settings',
                'guard_name' => 'web'
                ],
            )
        );


        /**
         * Export to PHP Array plugin for PHPMyAdmin
         * @version 5.0.4
         */

        /**
         * Database `eszinfo`
         */

        /* `eszinfo`.`settings` */
        $settings = array(
            array('id' => '10', 'setting' => 'Losung', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"0","rights":[],"home-view-top":"include.losung"}', 'created_at' => NULL, 'updated_at' => '2021-01-21 19:51:57'),
            array('id' => '50', 'setting' => 'Changelog', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"0","rights":[],"nav-user":{"name":"Changelog","link":"changelog"}}', 'created_at' => NULL, 'updated_at' => '2021-01-21 19:51:59'),
            array('id' => '1000', 'setting' => 'Nachrichten', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"0","rights":[],"home-view":"nachrichten.start","nav":{"name":"Nachrichten","link":"\\/","icon":"far fa-newspaper"},"adm-nav":{"adm-rights":["create posts"],"name":"neue Nachricht","link":"posts\\/create","icon":"fas fa-pen"}}', 'created_at' => NULL, 'updated_at' => '2021-01-21 19:52:00'),
            array('id' => '1050', 'setting' => 'Termine', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"0","rights":[],"home-view-top":"termine.nachricht","adm-nav":{"adm-rights":["edit termin"],"name":"neuer Termin","link":"termin\\/create","icon":"far fa-calendar-alt"}}', 'created_at' => NULL, 'updated_at' => '2021-01-21 19:52:01'),
            array('id' => '1100', 'setting' => 'Archiv', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"0","rights":[],"nav":{"name":"Archiv","link":"archiv","icon":"fas fa-archive"}}', 'created_at' => NULL, 'updated_at' => '2021-01-21 19:52:02'),
            array('id' => '1200', 'setting' => 'Dateien', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"0","rights":[],"nav":{"name":"Downloads","link":"files","icon":"fa fa-download"},"adm-nav":{"adm-rights":["upload files"],"name":"Datei hochladen","link":"files\\/create","icon":"fas fa-upload"}}', 'created_at' => NULL, 'updated_at' => '2021-01-21 19:52:14'),
            array('id' => '1300', 'setting' => 'Krankmeldung', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"0","rights":["view krankmeldung"],"nav":{"name":"Krankmeldung","link":"krankmeldung","icon":"fas fa-medkit"}}', 'created_at' => NULL, 'updated_at' => '2021-01-21 19:52:15'),
            array('id' => '1400', 'setting' => 'Reinigung', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"0","rights":[],"home-view":"reinigung.nachricht","nav":{"name":"Reinigungsplan","link":"reinigung","icon":"fas fa-broom"}}', 'created_at' => NULL, 'updated_at' => '2021-01-21 19:52:20'),
            array('id' => '1500', 'setting' => 'Listen', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"0","rights":[],"nav":{"name":"Listen","link":"listen","icon":"far fa-list-alt"},"adm-nav":{"adm-rights":["create terminliste"],"name":"neue Liste","link":"listen\\/create","icon":"far fa-list-alt"}}', 'created_at' => NULL, 'updated_at' => '2021-01-21 19:52:16'),
            array('id' => '1600', 'setting' => 'Schickzeiten', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"0","rights":["view schickzeiten"],"nav":{"name":"Schickzeiten","link":"schickzeiten","icon":"fas fa-clock"},"adm-nav":{"adm-rights":["edit schickzeiten"],"name":"Schickzeitenliste","link":"verwaltung\\/schickzeiten","icon":"fas fa-clock"}}', 'created_at' => NULL, 'updated_at' => '2021-01-21 19:52:17'),
            array('id' => '1700', 'setting' => 'Kontakt', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"0","rights":[],"nav":{"name":"Kontakt","link":"feedback","icon":"far fa-comment"}}', 'created_at' => NULL, 'updated_at' => '2021-01-21 19:52:18'),
            array('id' => '1800', 'setting' => 'Elternrat', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"0","rights":["view elternrat"],"home-view":"","nav":{"name":"Elternrat","link":"elternrat","icon":"fas fa-user-friends"}}', 'created_at' => NULL, 'updated_at' => '2021-01-21 19:52:23'),
            array('id' => '1900', 'setting' => 'Rechte', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"1","rights": [],"adm-nav":["adm-rights":["0":"edit permission"],"name": "Rechte","link":"roles","icon":"fas fa-user-tag"]]', 'created_at' => NULL, 'updated_at' => NULL),
            array('id' => '2000', 'setting' => 'Einstellungen', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"0","rights":[],"nav-user":{"name":"Einstellungen","link":"einstellungen"}}', 'created_at' => NULL, 'updated_at' => '2021-01-21 19:52:25'),
            array('id' => '2500', 'setting' => 'Benutzerverwaltung', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"1","rights":[],"adm-nav":{"adm-rights":["edit user"],"name":"Benutzer","link":"users","icon":"fas fa-user"}}', 'created_at' => NULL, 'updated_at' => '2021-01-21 19:52:27'),
            array('id' => '3000', 'setting' => 'Settings', 'description' => NULL, 'category' => 'module', 'options' => '["active":"1","rights":[],"adm-nav":["adm-rights":["0":"edit settings"], "name": "Module", "link":"settings", "icon":"fas fa-wrench"]]', 'created_at' => NULL, 'updated_at' => NULL),
            array('id' => '4000', 'setting' => 'Gruppen', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"1","rights":[],"adm-nav":{"adm-rights":["view groups"],"name":"Gruppen","link":"groups","icon":"fas fa-user-friends"}}', 'created_at' => NULL, 'updated_at' => '2021-01-21 19:52:29'),
            array('id' => '4001', 'setting' => 'Datenschutz', 'description' => NULL, 'category' => 'module', 'options' => '{"active":"0","rights":[],"nav-user":{"name":"Datenschutz","link":"datenschutz"}}', 'created_at' => '2021-01-21 18:27:23', 'updated_at' => '2021-01-21 19:52:31')
            );
        \Illuminate\Support\Facades\DB::table('settings')->insert($settings);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $setting = \App\Model\Settings::where('setting', 'Losung')->first();

        $settings = [
            'id' => $setting->id,
            'setting' => 'Losung',
            'description' => 'Zeigt im Nachrichtenbereich die Tageslosung an. Die Losungen müssen jedes Jahr aus der aktuellen csv-Datei importiert werden.',
            'category' => 'module',
            'options' => '{"active":"1","rights":[],"home-view-top":"include.losung","adm-nav":{"adm-rights":["edit settings"],"name":"Losung importieren","link":"settings\/losungen\/import","icon":"fas fa-file-import"}}'
        ];

        $setting->delete();

        DB::table('settings')->insert($settings);

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

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Model\Settings::insert([
            'setting' => 'meldepfl. Erkrankungen',
            'description' => 'Ermöglicht die Erfassung meldepflichtiger Erkrankungen direkt bei der Krankmeldung. Diese werden dann Im Nachrichtenbereich angezeigt.',
            'category' => 'module',
            'options' => '{
            "active":"1",
            "rights":[],
            "home-view-top":"krankmeldung.diseases",
            "adm-nav":
                {"adm-rights":["manage diseases"],"name":"neue Erkrankung","link":"diseases\/create","icon":"fas fa-pills"}
            }',
        ]);
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

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPflichtToRueckmeldungen extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rueckmeldungen', function (Blueprint $table) {
            $table->boolean('pflicht')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rueckmeldungen', function (Blueprint $table) {
            $table->removeColumn('pflicht');
        });
    }
}

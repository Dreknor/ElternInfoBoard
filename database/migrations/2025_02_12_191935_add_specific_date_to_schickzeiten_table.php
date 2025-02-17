<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpecificDateToSchickzeitenTable extends Migration
{
    public function up()
    {
        Schema::table('schickzeiten', function (Blueprint $table) {
            $table->date('specific_date')->nullable()->after('weekday');
            $table->integer('weekday')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('schickzeiten', function (Blueprint $table) {
            $table->dropColumn('specific_date');
        });
    }
}

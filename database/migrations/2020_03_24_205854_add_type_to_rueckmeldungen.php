<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToRueckmeldungen extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rueckmeldungen', function (Blueprint $table) {
            $table->string('type')->after('posts_id')->default('email');
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
            $table->removeColumn('type');
        });
    }
}

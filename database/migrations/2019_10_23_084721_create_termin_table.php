<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTerminTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('termine', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('terminname');
            $table->dateTime('start');
            $table->dateTime('ende');
            $table->timestamps();
        });

        Schema::create('groups_termine', function (Blueprint $table){
            $table->bigIncrements('id');
            $table->unsignedBigInteger('groups_id');
            $table->unsignedBigInteger('termin_id');

            $table->foreign('groups_id')->references('id')->on('groups');
            $table->foreign('termin_id')->references('id')->on('termine');
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::dropIfExists('groups_termine');
        Schema::dropIfExists('termine');
    }
}

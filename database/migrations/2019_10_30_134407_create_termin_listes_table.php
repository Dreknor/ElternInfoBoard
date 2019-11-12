<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTerminListesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('terminListen', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('listenname');
            $table->string('type');
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('besitzer');
            $table->boolean('visible_for_all');
            $table->boolean('active');
            $table->date('ende');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('groups_terminListen', function (Blueprint $table){
            $table->bigIncrements('id');
            $table->unsignedBigInteger('groups_id');
            $table->unsignedBigInteger('terminListen_id');

            $table->foreign('groups_id')->references('id')->on('groups');
            $table->foreign('terminListen_id')->references('id')->on('terminListen');
        });

        Schema::create('listen_termine', function (Blueprint $table){
            $table->bigIncrements('id');
            $table->unsignedBigInteger('terminListen_id');
            $table->dateTime('termin');
            $table->unsignedBigInteger('reserviert_fuer')->nullable();
            $table->string('comment')->nullable();

            $table->foreign('reserviert_fuer')->references('id')->on('users');
            $table->foreign('terminListen_id')->references('id')->on('terminListen');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('groups_terminListen');
        Schema::dropIfExists('terminListen');
    }
}

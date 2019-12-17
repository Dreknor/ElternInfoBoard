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
        Schema::create('listen', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('listenname');
            $table->string('type');
            $table->text('comment')->nullable();
            $table->unsignedInteger('duration')->default('30');
            $table->unsignedBigInteger('besitzer');
            $table->boolean('visible_for_all');
            $table->boolean('active');
            $table->date('ende');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('groups_listen', function (Blueprint $table){
            $table->bigIncrements('id');
            $table->unsignedBigInteger('groups_id');
            $table->unsignedBigInteger('liste_id');

            $table->foreign('groups_id')->references('id')->on('groups');
            $table->foreign('liste_id')->references('id')->on('listen');
        });

        Schema::create('listen_termine', function (Blueprint $table){
            $table->bigIncrements('id');
            $table->unsignedBigInteger('listen_id');
            $table->dateTime('termin');
            $table->unsignedBigInteger('reserviert_fuer')->nullable();
            $table->string('comment')->nullable();
            $table->timestamps();


            $table->foreign('reserviert_fuer')->references('id')->on('users');
            $table->foreign('listen_id')->references('id')->on('listen');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('listen_termine');
        Schema::dropIfExists('groups_listen');
        Schema::dropIfExists('listen');

    }
}

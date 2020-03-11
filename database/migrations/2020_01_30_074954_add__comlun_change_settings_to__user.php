<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddComlunChangeSettingsToUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->addColumn('boolean', 'changeSettings')->default(0)->nullable();
        });

        Schema::create('changelogs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('header');
            $table->text('text');
            $table->boolean('changeSettings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->removeColumn('changeSettings');
        });

        Schema::dropIfExists('changelogs');
    }
}

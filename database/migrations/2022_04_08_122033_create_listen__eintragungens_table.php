<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateListenEintragungensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('listen_eintragungen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('listen_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('eintragung')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('listen_id')->references('id')->on('listen');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('listen__eintragungens');
    }
}

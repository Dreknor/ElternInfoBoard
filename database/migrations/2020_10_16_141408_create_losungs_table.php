<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLosungsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('losungen', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date');
            $table->string('Losungsvers');
            $table->text('Losungstext');
            $table->string('Lehrtextvers');
            $table->text('Lehrtext');
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
        Schema::dropIfExists('losungen');
    }
}

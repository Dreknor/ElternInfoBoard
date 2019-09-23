<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRueckmeldungsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rueckmeldungen', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('posts_id');
            $table->string('empfaenger')->default('info@esz-radebeul.de');
            $table->date('ende');
            $table->longText('text');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('posts_id')->references('id')->on('posts');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rueckmeldungen');
    }
}

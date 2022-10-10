<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rueckmeldungen', function (Blueprint $table) {
            $table->boolean('multiple')->nullable();
            $table->integer('max_answers')->default(1);
        });

        Schema::table('users_rueckmeldungen', function (Blueprint $table) {
            $table->smallInteger('rueckmeldung_number')->default(1);
        });

        Schema::create('abfrage_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rueckmeldung_id');
            $table->string('type');
            $table->text('option');
            $table->timestamps();

            $table->foreign('rueckmeldung_id')->references('id')->on('rueckmeldungen')->cascadeOnDelete();
        });

        Schema::create('abfrage_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rueckmeldung_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('option_id');

            $table->text('answer')->nullable();
            $table->timestamps();

            $table->foreign('rueckmeldung_id')->references('id')->on('users_rueckmeldungen')->cascadeOnDelete();
            $table->foreign('option_id')->references('id')->on('abfrage_options')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('abfrage_options');
        Schema::dropIfExists('abfrage_answers');
        Schema::table('rueckmeldungen', function (Blueprint $table) {
            $table->dropColumn('max_answers');
            $table->dropColumn('multiple');
        });
        Schema::table('users_rueckmeldungen', function (Blueprint $table) {
            $table->dropColumn('rueckmeldung_number');
        });
    }
};

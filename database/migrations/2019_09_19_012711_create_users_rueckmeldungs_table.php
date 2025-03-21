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
    public function up(): void
    {
        Schema::create('users_rueckmeldungen', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('users_id');
            $table->longText('text');
            $table->timestamps();

            $table->foreign('post_id')->references('id')->on('posts');
            $table->foreign('users_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {

        Schema::table('users_rueckmeldungen', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
            $table->dropForeign(['users_id']);
        });
        Schema::dropIfExists('users_rueckmeldungen');
    }
};

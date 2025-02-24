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
        Schema::create('child_notices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('child_id');

            $table->text('notice');
            $table->date('date');
            $table->unsignedBigInteger('user_id');

            $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('child_notices');
    }
};

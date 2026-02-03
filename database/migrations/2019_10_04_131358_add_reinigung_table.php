<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reinigung', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('users_id')->nullable();
            $table->string('bereich');
            $table->date('datum');
            $table->text('aufgabe')->nullable();
            $table->text('bemerkung')->nullable();
            $table->timestamps();

            $table->foreign('users_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reinigung', function (Blueprint $table) {
            $table->dropForeign(['users_id']);
        });

        Schema::dropIfExists('reinigung');
    }
};

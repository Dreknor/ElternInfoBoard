<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Direkte Freigabe von Beiträgen und Terminen an einzelne Personen
 * (zusätzlich zur Freigabe über Gruppen, aktivierbar über general.allow_individual_recipients).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['post_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('termin_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('termin_id')->constrained('termine')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['termin_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('termin_user');
        Schema::dropIfExists('post_user');
    }
};

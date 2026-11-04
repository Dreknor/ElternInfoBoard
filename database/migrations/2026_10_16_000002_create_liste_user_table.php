<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Direkte Freigabe von Listen an einzelne Personen
 * (zusätzlich zur Freigabe über Gruppen, aktivierbar über general.allow_individual_recipients).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liste_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liste_id')->constrained('listen')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['liste_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liste_user');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Von Eltern ausgeblendete Termine (B-102). Gilt je Konto überall: Web, App und iCal-Feed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('termin_hidden', function (Blueprint $table) {
            $table->id();
            $table->foreignId('termin_id')->constrained('termine')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'termin_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('termin_hidden');
    }
};

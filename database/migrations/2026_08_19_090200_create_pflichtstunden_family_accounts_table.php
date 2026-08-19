<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pflichtstunden_family_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('family_key', 64);
            $table->unsignedInteger('period_year');
            $table->integer('opening_balance_minutes')->default(0);
            $table->integer('earned_minutes')->default(0);
            $table->integer('required_minutes')->default(0);
            $table->integer('closing_balance_minutes')->default(0);
            $table->integer('carried_to_next_minutes')->default(0);
            $table->boolean('carryover_applied')->default(false);
            $table->dateTime('last_calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['family_key', 'period_year']);
            $table->index(['period_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pflichtstunden_family_accounts');
    }
};

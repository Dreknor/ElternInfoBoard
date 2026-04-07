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
        Schema::table('stundenplan_schuljahre', function (Blueprint $table) {
            $table->string('schulform')->nullable()->after('name'); // z.B. "Grundschule", "Oberschule"
            $table->text('beschreibung')->nullable()->after('schulform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stundenplan_schuljahre', function (Blueprint $table) {
            $table->dropColumn(['schulform', 'beschreibung']);
        });
    }
};


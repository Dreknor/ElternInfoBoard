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
        Schema::table('vertretungen', function (Blueprint $table) {
            // Bestehende klasse-Spalte nullable machen
            $table->unsignedBigInteger('klasse')->nullable()->change();

            // Neue Spalte für Klassenkürzel hinzufügen
            $table->string('klasse_kurzform')->nullable()->after('klasse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vertretungen', function (Blueprint $table) {
            $table->dropColumn('klasse_kurzform');
            $table->unsignedBigInteger('klasse')->nullable(false)->change();
        });
    }
};


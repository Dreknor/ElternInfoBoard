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
        // Schuljahre für Stundenpläne
        Schema::create('stundenplan_schuljahre', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // z.B. "2025/2026"
            $table->date('datum_von');
            $table->date('datum_bis');
            $table->integer('sw_von'); // Schulwoche von
            $table->integer('sw_bis'); // Schulwoche bis
            $table->integer('tage_pro_woche')->default(5);
            $table->timestamp('zeitstempel')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Klassen
        Schema::create('stundenplan_klassen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schuljahr_id')->constrained('stundenplan_schuljahre')->cascadeOnDelete();
            $table->string('kurzform'); // z.B. "1Frue"
            $table->string('name')->nullable(); // Voller Name der Klasse
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['schuljahr_id', 'kurzform']);
        });

        // Zeitslots (Stunden)
        Schema::create('stundenplan_zeitslots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schuljahr_id')->constrained('stundenplan_schuljahre')->cascadeOnDelete();
            $table->integer('stunde'); // 1-6
            $table->time('zeit_von'); // 08:00
            $table->time('zeit_bis'); // 08:45
            $table->timestamps();

            $table->unique(['schuljahr_id', 'stunde']);
        });

        // Lehrer
        Schema::create('stundenplan_lehrer', function (Blueprint $table) {
            $table->id();
            $table->string('kuerzel')->unique(); // z.B. "Lis"
            $table->string('name')->nullable();
            $table->string('vorname')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Räume
        Schema::create('stundenplan_raeume', function (Blueprint $table) {
            $table->id();
            $table->string('kuerzel')->unique(); // z.B. ".Früh"
            $table->string('name')->nullable();
            $table->string('beschreibung')->nullable();
            $table->integer('kapazitaet')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Fächer
        Schema::create('stundenplan_faecher', function (Blueprint $table) {
            $table->id();
            $table->string('kuerzel')->unique(); // z.B. "MA"
            $table->string('name')->nullable(); // z.B. "Mathematik"
            $table->string('farbe')->nullable(); // Hex-Farbcode für UI
            $table->timestamps();
            $table->softDeletes();
        });

        // Haupttabelle für Stundenplan-Einträge
        Schema::create('stundenplan_eintraege', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schuljahr_id')->constrained('stundenplan_schuljahre')->cascadeOnDelete();
            $table->foreignId('zeitslot_id')->constrained('stundenplan_zeitslots')->cascadeOnDelete();
            $table->foreignId('fach_id')->nullable()->constrained('stundenplan_faecher')->nullOnDelete();
            $table->integer('wochentag'); // 1-5 (Mo-Fr)
            $table->string('unterrichts_id')->nullable(); // PlUn aus Import
            $table->text('bemerkung')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['schuljahr_id', 'wochentag', 'zeitslot_id']);
        });

        // Pivot: Stundenplan-Einträge zu Klassen (n:m)
        Schema::create('stundenplan_eintrag_klasse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eintrag_id')->constrained('stundenplan_eintraege')->cascadeOnDelete();
            $table->foreignId('klasse_id')->constrained('stundenplan_klassen')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['eintrag_id', 'klasse_id']);
        });

        // Pivot: Stundenplan-Einträge zu Lehrern (n:m)
        Schema::create('stundenplan_eintrag_lehrer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eintrag_id')->constrained('stundenplan_eintraege')->cascadeOnDelete();
            $table->foreignId('lehrer_id')->constrained('stundenplan_lehrer')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['eintrag_id', 'lehrer_id']);
        });

        // Pivot: Stundenplan-Einträge zu Räumen (n:m)
        Schema::create('stundenplan_eintrag_raum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eintrag_id')->constrained('stundenplan_eintraege')->cascadeOnDelete();
            $table->foreignId('raum_id')->constrained('stundenplan_raeume')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['eintrag_id', 'raum_id']);
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stundenplan_eintrag_raum');
        Schema::dropIfExists('stundenplan_eintrag_lehrer');
        Schema::dropIfExists('stundenplan_eintrag_klasse');
        Schema::dropIfExists('stundenplan_eintraege');
        Schema::dropIfExists('stundenplan_faecher');
        Schema::dropIfExists('stundenplan_raeume');
        Schema::dropIfExists('stundenplan_lehrer');
        Schema::dropIfExists('stundenplan_zeitslots');
        Schema::dropIfExists('stundenplan_klassen');
        Schema::dropIfExists('stundenplan_schuljahre');
    }
};


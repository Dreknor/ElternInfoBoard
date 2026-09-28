<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 5 – ucs_link_candidates: Initial-Linking-Workflow-Hilfstabelle.
 *
 * Wenn beim Sync ein lokales Kind (ucs_source='local') gefunden wird, dessen
 * Vor-/Nachname + Klasse zu einem UCS-Kind passt, wird KEIN Duplikat in children
 * angelegt, sondern ein Eintrag hier. Nach Admin-Bestätigung (UI oder ucs:link-child)
 * werden die Datensätze verschmolzen.
 *
 * Composite-UNIQUE (child_id, ucs_username) verhindert Duplikate auf DB-Ebene.
 *
 * @see docs/ucs-kelvin-integration-konzept.md §5.2 / §8 / §15.1
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ucs_link_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')
                ->constrained('children')
                ->cascadeOnDelete();
            $table->string('ucs_username', 120);
            $table->string('ucs_uuid', 64)->nullable();
            $table->string('reason', 64);           // 'name_match' | 'manual'
            $table->json('payload')->nullable();    // Original-Kelvin-Daten zum Review
            $table->timestamp('detected_at')->useCurrent();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->unique(['child_id', 'ucs_username'], 'ucs_link_candidates_child_username_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ucs_link_candidates');
    }
};


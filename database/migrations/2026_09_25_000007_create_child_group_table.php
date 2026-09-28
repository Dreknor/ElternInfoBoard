<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Weitere Gruppen/Klassen eines Kindes (z. B. UCS-Kombiklassen) zusätzlich zu
 * children.class_id / children.group_id. Grundlage für abgeleitete
 * Eltern-Gruppenmitgliedschaften.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §4.4, §5.4
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('source', 20)->default('manual');
            $table->timestamps();

            $table->unique(['child_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_group');
    }
};

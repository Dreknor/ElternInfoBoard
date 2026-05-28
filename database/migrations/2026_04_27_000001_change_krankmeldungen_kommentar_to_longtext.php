<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vergrößert die Spalte `kommentar` auf LONGTEXT, damit verschlüsselte
     * Werte (~1,5× Längenoverhead) sicher passen. Anschließend folgt eine
     * zweite Migration, die Bestandsdaten verschlüsselt.
     */
    public function up(): void
    {
        Schema::table('krankmeldungen', function (Blueprint $table) {
            $table->longText('kommentar')->change();
        });
    }

    public function down(): void
    {
        // Kein Roll-back: würde Datenverlust bei verschlüsselten Werten verursachen.
    }
};


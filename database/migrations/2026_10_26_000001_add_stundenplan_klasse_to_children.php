<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stundenplan-Klasse je Kind, von den Eltern gewählt, wenn die Klasse nicht
 * über die Gruppen des Kindes gefunden wird (Kurzform aus stundenplan_klassen).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->string('stundenplan_klasse', 50)->nullable()->after('class_id');
        });
    }

    public function down(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->dropColumn('stundenplan_klasse');
        });
    }
};

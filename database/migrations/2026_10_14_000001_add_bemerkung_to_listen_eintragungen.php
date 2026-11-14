<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Freitext der Eltern beim Übernehmen eines vorgegebenen Eintrags
 * (z. B. „Kuchen“ → „Apfelkuchen“).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listen_eintragungen', function (Blueprint $table) {
            $table->string('bemerkung', 255)->nullable()->after('eintragung');
        });
    }

    public function down(): void
    {
        Schema::table('listen_eintragungen', function (Blueprint $table) {
            $table->dropColumn('bemerkung');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zielgruppe für Links (eltern | eltern_schueler | schueler), wie bei Beiträgen und Terminen
 * (docs/konzept-schuelerkonten.md §3). Ohne aktives Modul Schülerkonten bleibt alles `eltern`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('links') && ! Schema::hasColumn('links', 'zielgruppe')) {
            Schema::table('links', function (Blueprint $table) {
                $table->string('zielgruppe', 20)->default('eltern')->after('for_all')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('links') && Schema::hasColumn('links', 'zielgruppe')) {
            Schema::table('links', function (Blueprint $table) {
                $table->dropIndex(['zielgruppe']);
                $table->dropColumn('zielgruppe');
            });
        }
    }
};

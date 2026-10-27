<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elternabfragen: Aktenablage und Neustart einzelner Fälle.
 *
 *  - Vorlagen können als „aktenrelevant“ gekennzeichnet werden (Ausdruck zur Schülerakte).
 *  - Je Auftrag wird vermerkt, welche Antwortversion für die Akte gedruckt wurde.
 *  - Einzelne Aufträge können neu gestartet werden, auch nach Abschluss der Abfrage.
 *  - Vorlagen können unbefristete Aufbewahrung vorsehen (z. B. Einwilligungen für die
 *    gesamte Schulzeit); gelöscht wird dann manuell durch die Verwaltung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiry_template_versions', function (Blueprint $table) {
            $table->boolean('record_relevant')->default(false)->after('requires_all_guardians');
            $table->boolean('retention_unlimited')->default(false)->after('retention_days');
        });

        Schema::table('inquiry_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('filed_response_id')->nullable()->after('current_response_id');
            $table->timestamp('filed_at')->nullable()->after('filed_response_id');
            $table->foreignId('filed_by')->nullable()->after('filed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable()->after('correction_until');
        });
    }

    public function down(): void
    {
        Schema::table('inquiry_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('filed_by');
            $table->dropColumn(['filed_response_id', 'filed_at', 'reopened_at']);
        });

        Schema::table('inquiry_template_versions', function (Blueprint $table) {
            $table->dropColumn(['record_relevant', 'retention_unlimited']);
        });
    }
};

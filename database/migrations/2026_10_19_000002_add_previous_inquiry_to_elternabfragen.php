<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elternabfragen: Wiederkehrende Abfragen (z. B. neues Schuljahr) übernehmen die Angaben
 * der Vorabfrage als Vorbefüllung. Jede Antwortversion hält fest, welche Angaben sich
 * gegenüber der Vorabfrage geändert haben – auch nachdem diese gelöscht wurde.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->foreignId('previous_inquiry_id')->nullable()->after('template_version_id')
                ->constrained('inquiries')->nullOnDelete();
        });

        Schema::table('inquiry_responses', function (Blueprint $table) {
            // Ohne Fremdschlüssel: Antworten der Vorabfrage werden nach Ablauf der Aufbewahrung gelöscht
            $table->unsignedBigInteger('baseline_response_id')->nullable()->after('previous_response_id');
            $table->json('baseline_changes')->nullable()->after('baseline_response_id');
        });
    }

    public function down(): void
    {
        Schema::table('inquiry_responses', function (Blueprint $table) {
            $table->dropColumn(['baseline_response_id', 'baseline_changes']);
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('previous_inquiry_id');
        });
    }
};

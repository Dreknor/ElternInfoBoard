<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elternabfragen: Änderungsmeldungen der Eltern zu einer bereits abgegebenen Antwort
 * (z. B. Umzug während des Schuljahres). Eltern melden die geänderten Angaben, das
 * Sekretariat prüft und übernimmt sie als neue Antwortversion oder lehnt sie ab.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiry_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained('inquiry_assignments')->cascadeOnDelete();
            $table->unsignedBigInteger('child_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Antwortversion, auf die sich die Meldung bezieht
            $table->unsignedBigInteger('base_response_id')->nullable();
            $table->json('answers');
            $table->text('message');
            $table->string('status', 20)->default('pending')->index();
            $table->string('channel', 10)->default('web');
            $table->string('ip_anonymized', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('handled_note')->nullable();
            // Bei Übernahme entstandene Antwortversion
            $table->unsignedBigInteger('response_id')->nullable();
            $table->timestamps();

            $table->index(['assignment_id', 'status']);
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->boolean('allow_change_requests')->default(true)->after('staff_visible');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn('allow_change_requests');
        });

        Schema::dropIfExists('inquiry_change_requests');
    }
};

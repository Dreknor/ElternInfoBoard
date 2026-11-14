<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elternabfragen: Erklärungsquittungen (§ 126b BGB). Pro Erklärung ein kleiner,
 * unveränderlicher Datensatz mit Prüfsummen. Das PDF wird nicht gespeichert, sondern
 * bei Bedarf deterministisch aus den Daten erzeugt; gespeichert wird nur seine SHA-256.
 * Die Quittungen einer Abfrage bilden eine Hash-Kette (prev_hash, sequence).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiry_receipts', function (Blueprint $table) {
            $table->id();
            // Öffentliche Quittungsnummer
            $table->uuid('uuid')->unique();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            // Position in der Kette der Abfrage (1, 2, 3 …) – Lücken zeigen gelöschte Quittungen
            $table->unsignedInteger('sequence');
            $table->foreignId('assignment_id')->constrained('inquiry_assignments')->cascadeOnDelete();
            $table->foreignId('response_id')->constrained('inquiry_responses')->cascadeOnDelete();
            $table->foreignId('confirmation_id')->nullable()->constrained('inquiry_confirmations')->cascadeOnDelete();
            $table->unsignedBigInteger('template_version_id');
            $table->unsignedInteger('response_version');
            $table->string('kind', 20);
            // Konto wird nach der endgültigen Löschung NULL – Namen sind deshalb eingefroren
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('declarant_name');
            $table->string('child_name');
            $table->string('school_name');
            $table->string('channel', 10);
            $table->timestamp('declared_at');
            // Den Eltern genannte Aufbewahrungsfrist der Abfrage (zum Zeitpunkt der Erklärung)
            $table->unsignedInteger('retention_days')->nullable();
            // Hochgeladene Dateien: Feldschlüssel => {name, size, sha256}
            $table->json('attachments')->nullable();
            $table->char('wording_hash', 64);
            $table->char('form_hash', 64);
            $table->char('answer_hash', 64);
            $table->char('prev_hash', 64)->nullable();
            $table->char('content_hash', 64)->unique();
            // Abgeleitet (nicht Teil von content_hash): SHA-256 des daraus erzeugten PDFs
            $table->char('pdf_sha256', 64)->nullable()->index();
            // Version des PDF-Layouts, mit dem pdf_sha256 entstanden ist
            $table->unsignedSmallInteger('layout_version')->default(1);
            $table->boolean('backfilled')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->unique(['inquiry_id', 'sequence']);
            $table->index(['assignment_id', 'response_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_receipts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ereignisprotokoll der An- und Abmeldungen im Hort.
 *
 * Die Tageszeile in child_check_ins bleibt der „aktuelle Stand“ für die Anwesenheitsliste.
 * Hier wird jede einzelne An-/Abmeldung festgehalten, damit Tagesverlauf und tatsächlicher
 * Betreuungsumfang nachvollziehbar bleiben. Korrekturen und Stornos überschreiben nichts:
 * der ursprüngliche Zeitpunkt bleibt in original_occurred_at erhalten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_attendance_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->date('date');
            $table->string('type', 16);          // check_in | check_out
            $table->dateTime('occurred_at');
            $table->string('source', 16);        // staff | auto | manual | import
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable(); // Begründung bei Nachträgen

            $table->dateTime('original_occurred_at')->nullable();
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('corrected_at')->nullable();
            $table->string('correction_note', 500)->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancel_note', 500)->nullable();

            $table->timestamps();

            $table->index(['child_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_attendance_events');
    }
};

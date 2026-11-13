<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sicherheitsprotokoll (Art. 32 DSGVO / § 27 DSG-EKD): nur zur Nachweisbarkeit
     * unbefugter Zugriffe, bewusst datensparsam und ohne Fremdschlüssel,
     * damit Einträge auch nach dem Löschen eines Kontos nachvollziehbar bleiben.
     */
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('event', 64)->index();
            // Betroffenes Konto (kein Foreign Key)
            $table->unsignedBigInteger('user_id')->nullable()->index();
            // Handelnde Person, falls abweichend (z. B. Admin oder impersonierender Admin)
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};

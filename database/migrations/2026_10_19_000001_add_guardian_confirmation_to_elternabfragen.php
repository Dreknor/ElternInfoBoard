<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elternabfragen: Vorlagen können verlangen, dass alle Sorgeberechtigten die Antwort
 * bestätigen. Bestätigungen gelten je Antwortversion – eine Korrektur setzt sie zurück.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiry_template_versions', function (Blueprint $table) {
            $table->boolean('requires_all_guardians')->default(false)->after('is_sensitive');
        });

        Schema::create('inquiry_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('inquiry_responses')->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained('inquiry_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 10)->default('web');
            $table->string('ip_anonymized', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('confirmed_at');
            $table->timestamps();

            $table->unique(['response_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_confirmations');

        Schema::table('inquiry_template_versions', function (Blueprint $table) {
            $table->dropColumn('requires_all_guardians');
        });
    }
};

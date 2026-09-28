<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Familie als explizite Einheit (Pflichtstunden, Reinigung, Rückmeldungen,
 * Termine). Eine Person gehört zu höchstens einer Familie (users.family_id).
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §4.2
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('source', 20)->default('auto');
            $table->boolean('is_locked')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('family_id')->nullable()->after('sorg2')
                ->constrained('families')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('family_id');
        });

        Schema::dropIfExists('families');
    }
};

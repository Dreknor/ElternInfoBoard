<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rückmeldungen pro Kind (E2/E7).
 *
 * - rueckmeldungen.scope: person | family | child. Neue Rückmeldungen: child,
 *   Bestand: family (so wurden sie beantwortet).
 * - users_rueckmeldungen.child_id / abfrage_answers.child_id: Antwort bezieht sich auf ein Kind.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rueckmeldungen', function (Blueprint $table) {
            $table->string('scope', 10)->default('child')->after('type');
        });
        DB::table('rueckmeldungen')->update(['scope' => 'family']);

        Schema::table('users_rueckmeldungen', function (Blueprint $table) {
            $table->foreignId('child_id')->nullable()->after('users_id')
                ->constrained('children')->nullOnDelete();
        });

        Schema::table('abfrage_answers', function (Blueprint $table) {
            $table->foreignId('child_id')->nullable()->after('user_id')
                ->constrained('children')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('abfrage_answers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('child_id');
        });

        Schema::table('users_rueckmeldungen', function (Blueprint $table) {
            $table->dropConstrainedForeignId('child_id');
        });

        Schema::table('rueckmeldungen', function (Blueprint $table) {
            $table->dropColumn('scope');
        });
    }
};

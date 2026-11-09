<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schülerkonten, Pakete S-07 und S-10 (docs/konzept-schuelerkonten.md §6.2, §9).
 *
 * - Einmal-Code für den Erstzugang bzw. ein neues Passwort: nur als HMAC gespeichert, 7 Tage gültig.
 * - Zustimmung der Eltern (nur wirksam bei `eltern_zustimmung`): letzter Stand gilt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'student_code_hash')) {
                $table->string('student_code_hash', 64)->nullable()->after('username');
                $table->timestamp('student_code_expires_at')->nullable()->after('student_code_hash');
            }
            if (! Schema::hasColumn('users', 'student_consent')) {
                $table->string('student_consent', 8)->nullable()->after('student_code_expires_at');
                $table->timestamp('student_consent_at')->nullable()->after('student_consent');
                $table->unsignedBigInteger('student_consent_by')->nullable()->after('student_consent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['student_consent_by', 'student_consent_at', 'student_consent', 'student_code_expires_at', 'student_code_hash'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

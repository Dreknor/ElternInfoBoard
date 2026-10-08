<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Listen: Begrenzung je Familie (bisheriges Verhalten) oder je Kind
 * („1 Helfer je Kind“). Eintragungen erhalten wie Termine (FAM-16) ein Kind.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §6.6
 */
return new class extends Migration
{
    public function up(): void
    {
        // Wiederholbar: MySQL/MariaDB führen DDL nicht transaktional aus
        if (! Schema::hasColumn('listen', 'booking_scope')) {
            Schema::table('listen', function (Blueprint $table) {
                $table->string('booking_scope', 10)->default('family')->after('multiple');
            });
        }

        // Altbestand: ungültige Nulldaten („0000-00-00 00:00:00“) lassen das Anlegen des
        // Fremdschlüssels im strikten SQL-Modus scheitern (Tabellenkopie)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("UPDATE listen_eintragungen SET updated_at = created_at WHERE CAST(updated_at AS CHAR) LIKE '0000-00-00%'");
            DB::statement("UPDATE listen_eintragungen SET created_at = NULL WHERE CAST(created_at AS CHAR) LIKE '0000-00-00%'");
        }

        if (! Schema::hasColumn('listen_eintragungen', 'child_id')) {
            Schema::table('listen_eintragungen', function (Blueprint $table) {
                $table->unsignedBigInteger('child_id')->nullable()->after('user_id');
            });
        }

        Schema::table('listen_eintragungen', function (Blueprint $table) {
            $table->foreign('child_id')->references('id')->on('children')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('listen_eintragungen', function (Blueprint $table) {
            $table->dropForeign(['child_id']);
            $table->dropColumn('child_id');
        });

        Schema::table('listen', function (Blueprint $table) {
            $table->dropColumn('booking_scope');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schülerkonten, Paket S-01 (docs/konzept-schuelerkonten.md §3).
 *
 * Nur additiv: bestehende Konten bleiben `standard`, bestehende Beiträge und Termine `eltern`.
 * `users.email` wird nullable, weil Schülerkonten nie eine E-Mail-Adresse haben.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'account_type')) {
                $table->string('account_type', 16)->default('standard')->after('email')->index();
            }
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 120)->nullable()->unique()->after('account_type');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        Schema::table('children', function (Blueprint $table) {
            if (! Schema::hasColumn('children', 'user_id')) {
                $table->foreignId('user_id')->nullable()->unique()->after('id')
                    ->constrained('users')->nullOnDelete();
            }
        });

        foreach (['posts', 'termine'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'zielgruppe')) {
                    $table->string('zielgruppe', 20)->default('eltern')->index();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['posts', 'termine'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'zielgruppe')) {
                    $table->dropIndex(['zielgruppe']);
                    $table->dropColumn('zielgruppe');
                }
            });
        }

        Schema::table('children', function (Blueprint $table) {
            if (Schema::hasColumn('children', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropUnique(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            }
            if (Schema::hasColumn('users', 'account_type')) {
                $table->dropIndex(['account_type']);
                $table->dropColumn('account_type');
            }
        });

        // users.email bleibt nullable: Zurücksetzen auf NOT NULL scheitert, sobald Konten ohne E-Mail existieren.
    }
};

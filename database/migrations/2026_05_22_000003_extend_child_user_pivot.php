<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 3 – child_user: Pivot-Erweiterung für UCS-Provisionierung.
 *
 * Bestehende Pivot-Einträge erhalten automatisch die Defaults (false / null).
 * Der Tabellenname, Primary Key und FKs bleiben unverändert.
 *
 * @see docs/ucs-kelvin-integration-konzept.md §4.1 / §8
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('child_user', function (Blueprint $table) {
            $table->boolean('is_auto_provisioned')->default(false)->after('user_id');
            $table->string('relation', 40)->nullable()->after('is_auto_provisioned');
            $table->timestamp('synced_at')->nullable()->after('relation');
        });
    }

    public function down(): void
    {
        Schema::table('child_user', function (Blueprint $table) {
            $table->dropColumn(['is_auto_provisioned', 'relation', 'synced_at']);
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trennt die OIDC-Kennung (sub-Claim des UCS-Keycloak, i. d. R. LDAP-entryUUID)
 * von users.ucs_uuid (= Kelvin record_uid aus dem Quellsystem).
 *
 * Beide Werte stammen aus unterschiedlichen Systemen und sind nie identisch.
 * Bisher überschrieben sich Login-Backfill und Nacht-Sync gegenseitig.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ucs_oidc_sub', 64)->nullable()->unique()->after('ucs_username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['ucs_oidc_sub']);
            $table->dropColumn('ucs_oidc_sub');
        });
    }
};

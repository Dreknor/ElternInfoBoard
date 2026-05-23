<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 1 – users: UCS-Provisionierungs-Spalten.
 *
 * @see docs/ucs-kelvin-integration-konzept.md §4.1 / §8
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ucs_uuid', 64)->nullable()->unique()->after('id');
            $table->string('ucs_username', 120)->nullable()->unique()->after('ucs_uuid');
            $table->string('ucs_school', 120)->nullable()->after('ucs_username');
            $table->timestamp('ucs_synced_at')->nullable()->after('ucs_school');
            $table->string('ucs_source', 16)->default('local')->after('ucs_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['ucs_uuid']);
            $table->dropUnique(['ucs_username']);
            $table->dropColumn(['ucs_uuid', 'ucs_username', 'ucs_school', 'ucs_synced_at', 'ucs_source']);
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 2 – children: UCS-Provisionierungs-Spalten + SoftDeletes.
 *
 * - Composite-UNIQUE (ucs_school, ucs_username) zukunftssicher für ggf. Multi-School.
 * - deleted_at wird nur ergänzt, falls noch nicht vorhanden (idempotent).
 *
 * @see docs/ucs-kelvin-integration-konzept.md §4.1 / §4.2 / §8
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->string('ucs_username', 120)->nullable()->after('last_name');
            $table->string('ucs_uuid', 64)->nullable()->unique()->after('ucs_username');
            $table->string('ucs_school', 120)->nullable()->after('ucs_uuid');
            $table->timestamp('ucs_synced_at')->nullable()->after('ucs_school');
            $table->string('ucs_source', 16)->default('local')->after('ucs_synced_at');

            // Composite-UNIQUE für Single- und zukünftigen Multi-School-Betrieb
            $table->unique(['ucs_school', 'ucs_username'], 'children_school_username_unique');

            // SoftDeletes nur ergänzen, falls noch nicht vorhanden (§4.2)
            if (! Schema::hasColumn('children', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->dropUnique('children_school_username_unique');
            $table->dropUnique(['ucs_uuid']);

            if (Schema::hasColumn('children', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            $table->dropColumn(['ucs_username', 'ucs_uuid', 'ucs_school', 'ucs_synced_at', 'ucs_source']);
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 4 – groups + group_user: UCS-Provisionierungs-Spalten + SoftDeletes.
 *
 * groups:
 *   - ucs_class_url (UNIQUE) → Idempotenz-Schlüssel für Klassen-Gruppen
 *   - ucs_source (default 'local') → Diskriminator
 *   - ucs_synced_at
 *   - deleted_at (SoftDeletes, nur falls noch nicht vorhanden)
 *
 * group_user:
 *   - is_auto_provisioned → Herzstück; schützt manuelle Zuordnungen vor Überschreiben
 *   - provisioned_via_child_id → Audit-FK
 *   - synced_at
 *
 * @see docs/ucs-kelvin-integration-konzept.md §4.1 / §7.3 / §8
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('ucs_class_url')->nullable()->unique()->after('id');
            $table->string('ucs_source', 16)->default('local')->after('ucs_class_url');
            $table->timestamp('ucs_synced_at')->nullable()->after('ucs_source');

            if (! Schema::hasColumn('groups', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('group_user', function (Blueprint $table) {
            $table->boolean('is_auto_provisioned')->default(false)->after('user_id');
            $table->foreignId('provisioned_via_child_id')
                ->nullable()
                ->after('is_auto_provisioned')
                ->constrained('children')
                ->nullOnDelete();
            $table->timestamp('synced_at')->nullable()->after('provisioned_via_child_id');
        });
    }

    public function down(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->dropForeign(['provisioned_via_child_id']);
            $table->dropColumn(['is_auto_provisioned', 'provisioned_via_child_id', 'synced_at']);
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropUnique(['ucs_class_url']);

            if (Schema::hasColumn('groups', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            $table->dropColumn(['ucs_class_url', 'ucs_source', 'ucs_synced_at']);
        });
    }
};


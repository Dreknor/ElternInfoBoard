<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FAM-16 (Konzept §4.4):
 * - children.status (applicant | active | left) mit Ein-/Austrittsdatum
 * - listen_termine.child_id: Termin (z. B. Elterngespräch) gehört zu einem Kind
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('class_id')->index();
            $table->date('entry_date')->nullable()->after('status');
            $table->date('exit_date')->nullable()->after('entry_date');
        });

        Schema::table('listen_termine', function (Blueprint $table) {
            $table->foreignId('child_id')->nullable()->after('reserviert_fuer')->constrained('children')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('listen_termine', function (Blueprint $table) {
            $table->dropConstrainedForeignId('child_id');
        });

        Schema::table('children', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'entry_date', 'exit_date']);
        });
    }
};

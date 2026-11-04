<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schülerkonten, Paket S-14: Unterhaltungstyp `student` (Chat Schüler ↔ Lehrkraft).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->enum('type', ['group', 'direct', 'student'])->default('group')->change();
        });
    }

    public function down(): void
    {
        DB::table('conversations')->where('type', 'student')->delete();

        Schema::table('conversations', function (Blueprint $table) {
            $table->enum('type', ['group', 'direct'])->default('group')->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('child_check_ins', function (Blueprint $table) {
            $table->date('lock_at')->nullable()->after('should_be');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('child_check_ins', function (Blueprint $table) {
            $table->dropColumn('lock_at');
        });
    }
};

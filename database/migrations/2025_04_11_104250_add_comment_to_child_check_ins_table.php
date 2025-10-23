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
            $table->string('comment', 256 )->nullable()->after('lock_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('child_check_ins', function (Blueprint $table) {
            $table->dropColumn('comment');
        });
    }
};

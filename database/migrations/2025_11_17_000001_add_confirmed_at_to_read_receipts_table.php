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
        Schema::table('read_receipts', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('final_reminder_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('read_receipts', function (Blueprint $table) {
            $table->dropColumn('confirmed_at');
        });
    }
};

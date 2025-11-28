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

        $exception = null;

        try {
            Schema::table('posts', function (Blueprint $table) {
                $table->dateTime('read_receipt_deadline')->nullable()->after('read_receipt');
            });
        } catch (\Exception $e) {
            $exception = $e;
        }

        try {
            Schema::table('read_receipts', function (Blueprint $table) {
                $table->timestamp('reminded_at')->nullable()->after('user_id');
                $table->timestamp('final_reminder_sent_at')->nullable()->after('reminded_at');
            });
        } catch (\Exception $e) {
            $exception = $e;
        }

        if ($exception) {
            Illuminate\Log\log($exception->getMessage());
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('read_receipt_deadline');
        });

        Schema::table('read_receipts', function (Blueprint $table) {
            $table->dropColumn(['reminded_at', 'final_reminder_sent_at']);
        });
    }
};


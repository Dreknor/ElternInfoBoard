<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Schritt 1: Duplikate bereinigen
        // Behalte nur den ältesten Eintrag pro (post_id, user_id)-Kombination
        DB::statement("
            DELETE t1 FROM read_receipts t1
            INNER JOIN read_receipts t2
            WHERE t1.id > t2.id
            AND t1.post_id = t2.post_id
            AND t1.user_id = t2.user_id
        ");

        // Schritt 2: Unique-Index hinzufügen
        Schema::table('read_receipts', function (Blueprint $table) {
            $table->unique(['post_id', 'user_id'], 'read_receipts_post_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('read_receipts', function (Blueprint $table) {
            $table->dropUnique('read_receipts_post_user_unique');
        });
    }
};

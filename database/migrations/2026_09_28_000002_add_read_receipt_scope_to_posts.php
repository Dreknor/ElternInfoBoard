<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lesebestätigung je Familie (bisher), je Person oder je Kind.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §6.7
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('read_receipt_scope', 10)->default('family')->after('read_receipt_deadline');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('read_receipt_scope');
        });
    }
};

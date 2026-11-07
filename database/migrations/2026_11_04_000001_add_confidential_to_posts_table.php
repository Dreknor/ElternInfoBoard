<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vertrauliche Beiträge: nur für Autor und Empfänger (Gruppenmitglieder) sichtbar –
 * keine Berechtigung („view all“, „edit posts“ …) hebt das auf.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('confidential')->default(false)->after('external');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('confidential');
        });
    }
};

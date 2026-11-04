<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ton bei Mitteilungen je Gerät (B-101). `null`: App-Version ohne Auswahl – Versand wie bisher mit Ton.
     */
    public function up(): void
    {
        Schema::table('user_devices', function (Blueprint $table) {
            $table->boolean('sound')->nullable()->after('app_version');
        });
    }

    public function down(): void
    {
        Schema::table('user_devices', function (Blueprint $table) {
            $table->dropColumn('sound');
        });
    }
};

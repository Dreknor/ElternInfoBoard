<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gerät des Browser-Abos: Plattform (ios, android, desktop) gegen doppelte Push-Mitteilungen
     * auf Geräten mit installierter App, Gerätename und letzte Nutzung für die Verwaltung der Abos.
     */
    public function up(): void
    {
        Schema::connection(config('webpush.database_connection'))->table(config('webpush.table_name'), function (Blueprint $table) {
            $table->string('platform', 20)->nullable()->after('content_encoding');
            $table->string('device_name')->nullable()->after('platform');
            $table->timestamp('last_seen_at')->nullable()->after('device_name');
        });
    }

    public function down(): void
    {
        Schema::connection(config('webpush.database_connection'))->table(config('webpush.table_name'), function (Blueprint $table) {
            $table->dropColumn(['platform', 'device_name', 'last_seen_at']);
        });
    }
};

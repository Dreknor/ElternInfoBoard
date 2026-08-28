<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Private Telefonnummer, wird z.B. im Anwesenheits-Modal für Betreuer angezeigt,
            // im Gegensatz zu `publicPhone` NICHT öffentlich in Gruppen sichtbar.
            $table->string('phone')->nullable()->after('publicPhone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};

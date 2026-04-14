<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('messenger_discoverable')->default(true)->after('is_active')
                ->comment('Ob der Nutzer über die Messenger-Personensuche gefunden werden kann');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('messenger_discoverable');
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schickzeiten', function (Blueprint $table) {
            $table->date('specific_date')->nullable()->after('weekday');
            $table->integer('weekday')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('schickzeiten', function (Blueprint $table) {
            $table->dropColumn('specific_date');
        });
    }
};

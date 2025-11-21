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
        Schema::table('listen', function (Blueprint $table) {
            $table->boolean('creates_pflichtstunden')->default(false)->after('make_new_entry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listen', function (Blueprint $table) {
            $table->dropColumn('creates_pflichtstunden');
        });
    }
};

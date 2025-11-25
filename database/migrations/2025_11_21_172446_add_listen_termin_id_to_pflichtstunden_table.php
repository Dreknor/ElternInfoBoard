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
        Schema::table('pflichtstunden', function (Blueprint $table) {
            $table->unsignedBigInteger('listen_termin_id')->nullable()->after('user_id');
            $table->foreign('listen_termin_id')->references('id')->on('listen_termine')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pflichtstunden', function (Blueprint $table) {
            $table->dropForeign(['listen_termin_id']);
            $table->dropColumn('listen_termin_id');
        });
    }
};

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
        Schema::table('users_rueckmeldungen', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('abfrage_answers', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('abfrage_options', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_rueckmeldungen', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('abfrage_answers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('abfrage_options', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};

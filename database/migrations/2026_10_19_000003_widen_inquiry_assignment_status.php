<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elternabfragen: Der Status `awaiting_confirmation` (21 Zeichen) passt nicht in varchar(20).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiry_assignments', function (Blueprint $table) {
            $table->string('status', 30)->default('open')->change();
        });
    }

    public function down(): void
    {
        Schema::table('inquiry_assignments', function (Blueprint $table) {
            $table->string('status', 20)->default('open')->change();
        });
    }
};

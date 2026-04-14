<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make start_date nullable in arbeitsgemeinschaften table.
     * GTAs ohne definierten Startzeitpunkt (z.B. laufen das ganze Schuljahr) sollen
     * start_date = NULL haben dürfen.
     */
    public function up(): void
    {
        Schema::table('arbeitsgemeinschaften', function (Blueprint $table) {
            $table->date('start_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('arbeitsgemeinschaften', function (Blueprint $table) {
            $table->date('start_date')->nullable(false)->change();
        });
    }
};


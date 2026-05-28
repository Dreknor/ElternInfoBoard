<?php

use App\Model\Module;
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
        Schema::table('settings_modules', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('category');
        });

        // Initialisiere sort_order anhand der aktuellen Datenbankreihenfolge (id)
        $modules = Module::orderBy('id')->get();
        foreach ($modules as $index => $module) {
            $module->sort_order = $index + 1;
            $module->saveQuietly();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings_modules', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};


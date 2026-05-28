<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Aktiviert das Familien-Dashboard-Modul (Familienübersicht) in der Navigationsleiste.
 * Wurde initial mit active=false angelegt – diese Migration korrigiert das für alle Installationen.
 */
return new class extends Migration
{
    public function up(): void
    {
        $module = DB::table('settings_modules')->where('setting', 'Familienübersicht')->first();

        if ($module) {
            $options           = json_decode($module->options, true);
            $options['active'] = true;

            DB::table('settings_modules')
                ->where('setting', 'Familienübersicht')
                ->update([
                    'options'    => json_encode($options),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $module = DB::table('settings_modules')->where('setting', 'Familienübersicht')->first();

        if ($module) {
            $options           = json_decode($module->options, true);
            $options['active'] = false;

            DB::table('settings_modules')
                ->where('setting', 'Familienübersicht')
                ->update([
                    'options'    => json_encode($options),
                    'updated_at' => now(),
                ]);
        }
    }
};


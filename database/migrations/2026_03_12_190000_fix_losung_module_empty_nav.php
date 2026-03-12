<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Entfernt den leeren 'nav'-Schlüssel aus dem Losung-Modul.
     * Ein leeres Array [] besteht den is_array()-Check im modules.blade.php,
     * führt aber zu "Undefined array key 'name'" beim Zugriff auf nav['name'].
     */
    public function up(): void
    {
        $module = DB::table('settings_modules')->where('setting', 'Losung')->first();

        if (!$module) {
            return;
        }

        $options = json_decode($module->options, true);

        if (!is_array($options)) {
            return;
        }

        // Leeres nav-Array entfernen – Losung hat keine Navigationsverknüpfung
        if (array_key_exists('nav', $options) && is_array($options['nav']) && empty($options['nav'])) {
            unset($options['nav']);
            DB::table('settings_modules')
                ->where('setting', 'Losung')
                ->update(['options' => json_encode($options)]);
        }
    }

    public function down(): void
    {
        $module = DB::table('settings_modules')->where('setting', 'Losung')->first();

        if (!$module) {
            return;
        }

        $options = json_decode($module->options, true);

        if (!is_array($options)) {
            return;
        }

        $options['nav'] = [];
        DB::table('settings_modules')
            ->where('setting', 'Losung')
            ->update(['options' => json_encode($options)]);
    }
};


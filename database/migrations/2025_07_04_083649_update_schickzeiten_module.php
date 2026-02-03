<?php

use App\Model\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $module = Module::where('setting', '=', 'Schickzeiten')->first();
        if ($module) {
            $options = $module->options ?? [];
            $options['home-view-top'] = 'child.include.home-header';
            $module->options = $options;
            $module->save();

        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $module = Module::where('setting', '=', 'Schickzeiten')->first();
        if ($module) {
            $options = $module->options ?? [];
            unset($options['home-view-top']);
            $module->options = $options;
            $module->save();
        }
    }
};

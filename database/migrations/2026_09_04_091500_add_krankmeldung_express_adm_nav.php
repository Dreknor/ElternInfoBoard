<?php

use App\Model\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Ergänzt das bestehende "Krankmeldung"-Modul um einen Verwaltungslink
 * ("adm-nav") zur neuen Express-Schnellerfassung für das Sekretariat.
 * Der Link erscheint in der Sidebar-Navigation unterhalb "Verwaltung",
 * sobald der Nutzer die Berechtigung "create krankmeldung express" besitzt.
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            $module = Module::where('setting', 'Krankmeldung')->first();

            if ($module) {
                $options = $module->options ?? [];
                $options['adm-nav'] = [
                    'adm-rights' => ['create krankmeldung express'],
                    'name' => 'Krankmeldung Express',
                    'link' => 'krankmeldung/express',
                    'icon' => 'fas fa-bolt',
                ];

                $module->options = $options;
                $module->save();
            }

            Artisan::call('cache:clear');
        } catch (\Exception $e) {
            Log::error('Failed to add "Krankmeldung Express" adm-nav entry: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        $module = Module::where('setting', 'Krankmeldung')->first();

        if ($module) {
            $options = $module->options ?? [];
            unset($options['adm-nav']);
            $module->options = $options;
            $module->save();
        }
    }
};

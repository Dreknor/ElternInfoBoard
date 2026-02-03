<?php

namespace Database\Seeders;

use App\Model\Module;
use Illuminate\Database\Seeder;

class UpdateNachrichtenModuleLinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Hole das Nachrichten-Modul
        $nachrichtenModule = Module::query()->where('setting', 'Nachrichten')->first();

        if ($nachrichtenModule) {
            // Aktualisiere die Optionen
            $options = $nachrichtenModule->options;

            // Entferne home-view, da wir jetzt ein Dashboard haben
            if (isset($options['home-view'])) {
                unset($options['home-view']);
            }

            // Aktualisiere den Nav-Link auf /nachrichten
            if (isset($options['nav'])) {
                $options['nav']['link'] = 'nachrichten';
                $options['nav']['bottom-nav'] = 'true'; // Für mobile Navigation
            }

            $nachrichtenModule->options = $options;
            $nachrichtenModule->save();

            $this->command->info('Nachrichten-Modul wurde aktualisiert: Link zeigt jetzt auf /nachrichten');
        } else {
            $this->command->warn('Nachrichten-Modul nicht gefunden');
        }

        // Optional: Termine-Modul ebenfalls anpassen
        $termineModule = Module::query()->where('setting', 'Termine')->first();

        if ($termineModule) {
            $options = $termineModule->options;

            // Entferne home-view-top
            if (isset($options['home-view-top'])) {
                unset($options['home-view-top']);
            }

            // Füge Navigation hinzu, falls nicht vorhanden
            if (! isset($options['nav'])) {
                $options['nav'] = [
                    'name' => 'Termine',
                    'link' => 'termin',
                    'icon' => 'far fa-calendar-alt',
                    'bottom-nav' => 'true',
                ];
            } else {
                $options['nav']['link'] = 'termin';
                $options['nav']['bottom-nav'] = 'true';
            }

            $termineModule->options = $options;
            $termineModule->save();

            $this->command->info('Termine-Modul wurde aktualisiert: Link zeigt jetzt auf /termin');
        }
    }
}

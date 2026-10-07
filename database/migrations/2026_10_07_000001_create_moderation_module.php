<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gemeinsamer Verwaltungs-Menüpunkt "Moderation" für gemeldete Beiträge und Messenger-Nachrichten.
 *
 * Ersetzt den bisherigen adm-nav-Eintrag des Moduls "Eltern-Nachrichten", der nur die
 * Messenger-Meldungen erreichbar machte (Beitragsmeldungen hatten keinen Menüpunkt).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('settings_modules')->where('setting', 'Moderation')->exists()) {
            $module = [
                'setting'     => 'Moderation',
                'category'    => 'module',
                'description' => 'Gemeinsame Anlaufstelle zur Prüfung gemeldeter Beiträge und Messenger-Nachrichten.',
                'options'     => json_encode([
                    'active'  => true,
                    'rights'  => [],
                    'adm-nav' => [
                        'name'       => 'Moderation',
                        'link'       => 'verwaltung/moderation',
                        'icon'       => 'fas fa-shield-alt',
                        'adm-rights' => ['edit settings', 'moderate messages'],
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('settings_modules', 'sort_order')) {
                $module['sort_order'] = (int) DB::table('settings_modules')->max('sort_order') + 1;
            }

            DB::table('settings_modules')->insert($module);
        }

        $this->updateMessengerAdmNav(function (array $options) {
            if (($options['adm-nav']['link'] ?? null) === 'messenger/admin/reports') {
                unset($options['adm-nav']);
            }

            return $options;
        });

        Cache::forget('modules');
    }

    public function down(): void
    {
        DB::table('settings_modules')->where('setting', 'Moderation')->delete();

        $this->updateMessengerAdmNav(function (array $options) {
            $options['adm-nav'] ??= [
                'name'       => 'Nachrichten-Moderation',
                'link'       => 'messenger/admin/reports',
                'icon'       => 'fas fa-shield-alt',
                'adm-rights' => ['moderate messages'],
            ];

            return $options;
        });

        Cache::forget('modules');
    }

    private function updateMessengerAdmNav(callable $callback): void
    {
        $module = DB::table('settings_modules')->where('setting', 'Eltern-Nachrichten')->first();
        if (! $module) {
            return;
        }

        $options = json_decode($module->options, true) ?? [];

        DB::table('settings_modules')
            ->where('id', $module->id)
            ->update(['options' => json_encode($callback($options))]);
    }
};

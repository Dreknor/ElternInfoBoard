<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Behebt fehlerhafte options-Werte in settings_modules:
     * 1. Losung: leeres 'nav'-Array entfernen (führt zu "Undefined array key 'name'")
     * 2. Seiten / Settings: doppeltes json_encode rückgängig machen
     *    (rights, nav, adm-nav wurden als JSON-Strings statt Arrays gespeichert,
     *     was zu "count(): Argument must be of type Countable|array, string given" führt)
     */
    public function up(): void
    {
        // --- 1. Losung: leeres nav-Array entfernen ---
        $this->fixModule('Losung', function (array $options): array {
            if (array_key_exists('nav', $options) && is_array($options['nav']) && empty($options['nav'])) {
                unset($options['nav']);
            }
            return $options;
        });

        // --- 2. Seiten: doppelt enkodierte Felder dekodieren ---
        $this->fixModule('Seiten', function (array $options): array {
            foreach (['rights', 'nav', 'adm-nav'] as $key) {
                if (isset($options[$key]) && is_string($options[$key])) {
                    $decoded = json_decode($options[$key], true);
                    if (is_array($decoded)) {
                        $options[$key] = $decoded;
                    }
                }
            }
            // adm-rights in adm-nav ebenfalls dekodieren
            if (isset($options['adm-nav']['adm-rights']) && is_string($options['adm-nav']['adm-rights'])) {
                $decoded = json_decode($options['adm-nav']['adm-rights'], true);
                if (is_array($decoded)) {
                    $options['adm-nav']['adm-rights'] = $decoded;
                }
            }
            return $options;
        });

        // --- 3. Settings: doppelt enkodierte Felder dekodieren ---
        $this->fixModule('Settings', function (array $options): array {
            foreach (['rights', 'adm-nav'] as $key) {
                if (isset($options[$key]) && is_string($options[$key])) {
                    $decoded = json_decode($options[$key], true);
                    if (is_array($decoded)) {
                        $options[$key] = $decoded;
                    }
                }
            }
            return $options;
        });
    }

    public function down(): void
    {
        // Losung: leeres nav wiederherstellen
        $this->fixModule('Losung', function (array $options): array {
            $options['nav'] = [];
            return $options;
        });

        // Seiten / Settings: Rückgängigmachen nicht sinnvoll (kaputte Daten)
    }

    private function fixModule(string $setting, callable $transform): void
    {
        $module = DB::table('settings_modules')->where('setting', $setting)->first();
        if (!$module) {
            return;
        }

        $options = json_decode($module->options, true);
        if (!is_array($options)) {
            return;
        }

        $fixed = $transform($options);
        DB::table('settings_modules')
            ->where('setting', $setting)
            ->update(['options' => json_encode($fixed)]);
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Modul „Elternabfragen“ und seine Rechte.
 *
 * Das Modul wird inaktiv angelegt: Erst nach Rechtevergabe (Sekretariat, Schulleitung,
 * Mitarbeitende) aktiviert die Verwaltung es unter Einstellungen › Module.
 * Rollen mit „edit settings“ erhalten alle Rechte, damit die Einrichtung möglich ist.
 */
return new class extends Migration
{
    public const PERMISSIONS = [
        'manage elternabfragen' => 'Vorlagen und Abfragen vorbereiten, Rücklauf überwachen, erinnern, Nachfristen setzen (Sekretariat).',
        'approve elternabfragen' => 'Vorlagen prüfen und Abfragen vor dem Versand freigeben (Schulleitung bzw. benannte Stelle).',
        'view elternabfragen answers' => 'Antwortinhalte einsehen und Antworten prüfen bzw. für Mitarbeitende freigeben.',
        'export elternabfragen' => 'Antworten exportieren (CSV/Excel, protokolliert).',
        'read elternabfragen' => 'Mitarbeitende: geprüfte Antworten freigegebener Abfragen lesen.',
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $name => $description) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $permission->module = 'Elternabfragen';
            $permission->description = $description;
            $permission->save();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::query()
            ->whereHas('permissions', fn ($query) => $query->where('name', 'edit settings'))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo(array_keys(self::PERMISSIONS)));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (! DB::table('settings_modules')->where('setting', 'Elternabfragen')->exists()) {
            $module = [
                'setting' => 'Elternabfragen',
                'category' => 'module',
                'description' => 'Digitale Elternabfragen: geprüfte Vorlagen, Freigabe vor Versand, Rücklaufkontrolle, Erinnerungen und protokollierter Export.',
                'options' => json_encode([
                    'active' => false,
                    'rights' => [],
                    'nav' => [
                        'name' => 'Elternabfragen',
                        'link' => 'elternabfragen',
                        'icon' => 'fas fa-clipboard-check',
                    ],
                    'adm-nav' => [
                        'name' => 'Elternabfragen',
                        'link' => 'verwaltung/elternabfragen',
                        'icon' => 'fas fa-clipboard-check',
                        'adm-rights' => array_keys(self::PERMISSIONS),
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

        Cache::forget('modules');
    }

    public function down(): void
    {
        DB::table('settings_modules')->where('setting', 'Elternabfragen')->delete();
        Permission::whereIn('name', array_keys(self::PERMISSIONS))->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::forget('modules');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Modul „Links“ und sein Verwaltungsrecht.
 *
 * Das Modul wird inaktiv angelegt; die Schule aktiviert es unter Einstellungen › Module.
 * Lesen dürfen alle angemeldeten Nutzer (je nach Sichtbarkeit des Links),
 * pflegen dürfen Rollen mit „edit links“. Rollen mit „edit settings“ erhalten das Recht.
 */
return new class extends Migration
{
    public const PERMISSION = 'edit links';

    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => self::PERMISSION, 'guard_name' => 'web']);
        $permission->module = 'Links';
        $permission->description = 'Links anlegen, bearbeiten, sortieren und löschen.';
        $permission->save();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::query()
            ->whereHas('permissions', fn ($query) => $query->where('name', 'edit settings'))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo(self::PERMISSION));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (! DB::table('settings_modules')->where('setting', 'Links')->exists()) {
            $module = [
                'setting' => 'Links',
                'category' => 'module',
                'description' => 'Wichtige Links für Eltern und Mitarbeitende (z. B. Schulhomepage, Mensa-Bestellung, Formulare) – für alle oder für ausgewählte Gruppen, im Web und in der App.',
                'options' => json_encode([
                    'active' => false,
                    'rights' => [],
                    'nav' => [
                        'name' => 'Links',
                        'link' => 'links',
                        'icon' => 'fas fa-link',
                    ],
                    'adm-nav' => [
                        'name' => 'Links verwalten',
                        'link' => 'links/verwaltung',
                        'icon' => 'fas fa-link',
                        'adm-rights' => [self::PERMISSION],
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
        DB::table('settings_modules')->where('setting', 'Links')->delete();
        Permission::where('name', self::PERMISSION)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::forget('modules');
    }
};

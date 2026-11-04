<?php

use App\Support\StudentAccounts;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Modul „Schülerkonten“ und sein Recht (docs/konzept-schuelerkonten.md §1, §3, Paket S-01).
 *
 * Das Modul wird inaktiv angelegt und ist zusätzlich instanzgesperrt (`config/modules.php`):
 * Ohne MODULES_UNLOCKED erscheint es weder in der Modulverwaltung noch in der Rechteverwaltung.
 * Das Recht wird bewusst keiner Rolle zugeordnet; die Schule vergibt es nach dem Freischalten.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => StudentAccounts::PERMISSION_CREATE_POSTS, 'guard_name' => 'web']);
        $permission->module = StudentAccounts::MODULE;
        $permission->description = 'Nachrichten und Termine auch für Schülerinnen und Schüler sichtbar machen („Sichtbar für“).';
        $permission->save();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (! DB::table('settings_modules')->where('setting', StudentAccounts::MODULE)->exists()) {
            DB::table('settings_modules')->insert([
                'setting' => StudentAccounts::MODULE,
                'category' => 'module',
                'description' => 'Eigene, stark eingeschränkte Konten für Schülerinnen und Schüler (Login über SSO oder Benutzername).',
                'options' => json_encode([
                    'active' => false,
                    'rights' => [],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings_modules')->where('setting', StudentAccounts::MODULE)->delete();
        Permission::where('name', StudentAccounts::PERMISSION_CREATE_POSTS)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

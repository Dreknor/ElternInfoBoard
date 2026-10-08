<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Legt die Permission "disable news mail" an: Nur Nutzer mit diesem Recht können in den
 * Benachrichtigungseinstellungen die E-Mail-Zusammenfassung für neue Nachrichten abwählen.
 * Das Recht wird keiner Rolle automatisch zugewiesen, sondern über die Rollenverwaltung vergeben.
 */
return new class extends Migration
{
    private const PERMISSION_NAME = 'disable news mail';

    public function up(): void
    {
        try {
            $permission = Permission::firstOrCreate(
                ['name' => self::PERMISSION_NAME, 'guard_name' => 'web'],
            );

            $permission->module = 'Nachrichten';
            $permission->description = 'E-Mail-Zusammenfassung für neue Nachrichten in den Benachrichtigungseinstellungen abwählen.';
            $permission->save();

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Exception $e) {
            Log::error('Failed to create "disable news mail" permission: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        Permission::where('name', self::PERMISSION_NAME)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Legt die Permission "view children overview" an: Kinderübersicht (care/children)
 * aufrufen und Kontaktdaten der Sorgeberechtigten einsehen – ohne Bearbeitungsrechte.
 *
 * Vergeben wird sie an die Rollen Mitarbeiter und Administrator sowie an alle Rollen,
 * die bereits "edit schickzeiten" besitzen (bisheriger Zugang zur Seite).
 * Über die Rollenverwaltung kann sie anschließend angepasst werden.
 */
return new class extends Migration
{
    private const PERMISSION_NAME = 'view children overview';

    public function up(): void
    {
        try {
            $permission = Permission::firstOrCreate(
                ['name' => self::PERMISSION_NAME, 'guard_name' => 'web'],
            );

            $permission->module = 'Care';
            $permission->description = 'Kinderübersicht aufrufen und Kontaktdaten der Sorgeberechtigten einsehen.';
            $permission->save();

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            Role::query()
                ->where('guard_name', 'web')
                ->where(function ($query) {
                    $query->whereIn('name', ['Mitarbeiter', 'Administrator'])
                        ->orWhereHas('permissions', fn ($q) => $q->where('name', 'edit schickzeiten'));
                })
                ->get()
                ->each(fn (Role $role) => $role->givePermissionTo($permission));

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Exception $e) {
            Log::error('Failed to create "view children overview" permission: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        Permission::where('name', self::PERMISSION_NAME)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

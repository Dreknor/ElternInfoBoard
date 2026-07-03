<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Verteilt alle Rechte sinnvoll auf die Rollen.
 * Legt die Rollen "Eltern" und "Hort" an, falls noch nicht vorhanden.
 *
 * Bestehende Rollen: Administrator, Mitarbeiter, Elternrat, Sekretariat
 * Neue Rollen:       Eltern, Hort
 */
class RolesPermissionsDistributionSeeder extends Seeder
{
    public function run(): void
    {
        // Cache zurücksetzen, damit Spatie immer aktuell arbeitet
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // -------------------------------------------------------------------------
        // 1. Fehlende Rollen anlegen
        // -------------------------------------------------------------------------
        $roles = [
            'Administrator',
            'Mitarbeiter',
            'Elternrat',
            'Sekretariat',
            'Eltern',   // neu
            'Hort',     // neu
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // -------------------------------------------------------------------------
        // 2. Rollenzuweisungen
        // -------------------------------------------------------------------------

        // Hilfsfunktion: Rechte zuweisen (nur vorhandene Permissions)
        $assign = static function (string $roleName, array $permissions): void {
            $role = Role::findByName($roleName, 'web');
            $existing = Permission::whereIn('name', $permissions)->pluck('name')->toArray();
            if (!empty($existing)) {
                $role->syncPermissions($existing);
            }
        };

        // ─── Administrator ────────────────────────────────────────────────────────
        // Erhält alle vorhandenen Rechte
        $adminRole = Role::findByName('Administrator', 'web');
        $adminRole->syncPermissions(Permission::where('guard_name', 'web')->get());

        // ─── Mitarbeiter ─────────────────────────────────────────────────────────
        $assign('Mitarbeiter', [
            // Inhalte
            'create posts',
            'edit posts',
            'release posts',
            'delete posts',
            'make sticky',
            'upload files',
            'upload great files',
            'use scriptTag',
            // Umfragen
            'create polls',
            // Termine
            'edit termin',
            'edit terminliste',
            'create terminliste',
            // Elternrat
            'view elternrat',
            // Reinigung
            'view reinigung',
            'edit reinigung',
            // Schick-/Abholzeiten
            'view schickzeiten',
            'download schickzeiten',
            // Krankmeldungen
            'view krankmeldung',
            'see diseases',
            // Gruppen
            'view groups',
            'edit groups',
            // Mitarbeiter-Board
            'view mitarbeiterboard',
            // Rückmeldungen
            'view rueckmeldungen',
            // Nachrichten
            'send urgent message',
            // Stundenplan
            'view stundenplan',
            'view stundenplan teacher',
            'view stundenplan room',
            // Vertretungsplan
            'view vertretungsplan',
            'view vertretungsplan all',
            // Seiten / externe Angebote
            'view sites',
            'view external offer',
            // Kontaktformular
            'show in contact form',
            // Allgemein
            'view all',
            'view protected',
            // Passwortlos-Login (Komfort für Mitarbeiter)
            'allow password-less-login',
        ]);

        // ─── Elternrat ────────────────────────────────────────────────────────────
        $assign('Elternrat', [
            // Elternrat-Bereich
            'view elternrat',
            'delete elternrat file',
            // Inhalte (eigene Beiträge im Elternrat-Bereich)
            'create posts',
            'upload files',
            // Umfragen
            'create polls',
            // Reinigung
            'view reinigung',
            // Rückmeldungen einsehen
            'view rueckmeldungen',
            // Termine einsehen
            'view all',
            // Stundenplan
            'view stundenplan',
            // Vertretungsplan
            'view vertretungsplan',
            // Gruppen
            'view groups',
            // Seiten / externe Angebote
            'view sites',
            'view external offer',
        ]);

        // ─── Sekretariat ─────────────────────────────────────────────────────────
        $assign('Sekretariat', [
            // Nutzerverwaltung
            'edit user',
            'import user',
            'set password',
            'assign roles to users',
            'role is assignable',
            // Inhalte
            'create posts',
            'edit posts',
            'release posts',
            'delete posts',
            'make sticky',
            'upload files',
            'upload great files',
            // Termine
            'edit termin',
            'edit terminliste',
            'create terminliste',
            // Elternrat
            'view elternrat',
            // Reinigung
            'view reinigung',
            // Schick-/Abholzeiten
            'view schickzeiten',
            'edit schickzeiten',
            'download schickzeiten',
            // Krankmeldungen
            'view krankmeldung',
            'see diseases',
            'manage diseases',
            'download krankmeldungen',
            // Rückmeldungen
            'view rueckmeldungen',
            'manage rueckmeldungen',
            // Gruppen
            'view groups',
            'edit groups',
            // Mitarbeiter-Board
            'view mitarbeiterboard',
            // Nachrichten / Mails
            'send urgent message',
            'see mails',
            // Stundenplan
            'view stundenplan',
            'view stundenplan teacher',
            'view stundenplan room',
            // Vertretungsplan
            'view vertretungsplan',
            'view vertretungsplan all',
            // Seiten
            'view sites',
            'create sites',
            'view external offer',
            // Allgemein
            'view all',
            'view protected',
            'view rueckmeldungen',
            // Dateien
            'scan files',
            // Logs
            'see logs',
            // Passwortlos-Login
            'allow password-less-login',
        ]);

        // ─── Eltern ───────────────────────────────────────────────────────────────
        // Können lesen, Krankmeldung einreichen und eigene Schickzeiten sehen
        $assign('Eltern', [
            // Basisrechte
            'view all',
            // Schick-/Abholzeiten (eigene Kinder)
            'view schickzeiten',
            // Krankmeldung einreichen
            'view krankmeldung',
            // Stundenplan (Klasse des Kindes)
            'view stundenplan',
            // Vertretungsplan
            'view vertretungsplan',
            // Gruppen
            'view groups',
            // Rückmeldungen
            'view rueckmeldungen',
            // Seiten / externe Angebote
            'view sites',
            'view external offer',
            // Eigene Gruppe erstellen (z. B. Klassen-WhatsApp-Ersatz)
            'create own group',
            // Passwortlos-Login (bequemer Zugang für Eltern)
            'allow password-less-login',
        ]);

        // ─── Hort ─────────────────────────────────────────────────────────────────
        // Hortbetreuer arbeiten wie Mitarbeiter, jedoch ohne erweiterte Admin-Rechte
        $assign('Hort', [
            // Inhalte
            'create posts',
            'edit posts',
            'release posts',
            'upload files',
            'make sticky',
            // Schick-/Abholzeiten (Kernaufgabe des Horts)
            'view schickzeiten',
            'edit schickzeiten',
            'download schickzeiten',
            // Krankmeldungen
            'view krankmeldung',
            'see diseases',
            // Gruppen
            'view groups',
            'edit groups',
            'create own group',
            // Mitarbeiter-Board
            'view mitarbeiterboard',
            // Rückmeldungen
            'view rueckmeldungen',
            'manage rueckmeldungen',
            // Stundenplan
            'view stundenplan',
            // Vertretungsplan
            'view vertretungsplan',
            // Reinigung
            'view reinigung',
            // Seiten / externe Angebote
            'view sites',
            'view external offer',
            // Nachrichten
            'send urgent message',
            // Allgemein
            'view all',
            'view protected',
            // Passwortlos-Login
            'allow password-less-login',
        ]);

        // -------------------------------------------------------------------------
        // 3. Cache zurücksetzen
        // -------------------------------------------------------------------------
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('✓ Rollen "Eltern" und "Hort" erstellt, alle Rechte sinnvoll verteilt.');
    }
}


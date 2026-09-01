<?php

namespace Database\Seeders;

use App\Model\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissionsCatalog() as $permissionData) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionData['name'],
                'guard_name' => $permissionData['guard_name'],
            ]);

            $permission->module = $permissionData['module'];
            $permission->description = $permissionData['description'];
            $permission->save();
        }

        foreach (['Administrator', 'Mitarbeiter', 'Elternrat', 'Sekretariat'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $admin = Role::findByName('Administrator', 'web');
        $elternrat = Role::findByName('Elternrat', 'web');

        $admin->givePermissionTo(['edit permission', 'edit user']);
        $elternrat->givePermissionTo(['view elternrat']);

        $user = User::find(1);
        if ($user) {
            $user->assignRole(['Administrator', 'Mitarbeiter']);
            $user->givePermissionTo(['edit permission', 'edit user']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array<int, array{name: string, guard_name: string, module: string, description: string}>
     */
    private function permissionsCatalog(): array
    {
        return [
            ['name' => 'edit permission', 'guard_name' => 'web', 'module' => 'Benutzerverwaltung', 'description' => 'Rollen und Berechtigungen verwalten.'],
            ['name' => 'edit user', 'guard_name' => 'web', 'module' => 'Benutzerverwaltung', 'description' => 'Benutzerdaten bearbeiten.'],
            ['name' => 'create posts', 'guard_name' => 'web', 'module' => 'Nachrichten', 'description' => 'Neue Beiträge erstellen.'],
            ['name' => 'view all', 'guard_name' => 'web', 'module' => 'Nachrichten', 'description' => 'Grundlegenden Zugriff auf Inhalte erhalten.'],
            ['name' => 'edit posts', 'guard_name' => 'web', 'module' => 'Nachrichten', 'description' => 'Bestehende Beiträge bearbeiten.'],
            ['name' => 'upload files', 'guard_name' => 'web', 'module' => 'Dateien', 'description' => 'Dateien hochladen.'],
            ['name' => 'import user', 'guard_name' => 'web', 'module' => 'Benutzerverwaltung', 'description' => 'Benutzer per Import anlegen.'],
            ['name' => 'release posts', 'guard_name' => 'web', 'module' => 'Nachrichten', 'description' => 'Beiträge veröffentlichen.'],
            ['name' => 'use scriptTag', 'guard_name' => 'web', 'module' => 'Nachrichten', 'description' => 'Script-Tags in Inhalten verwenden.'],
            ['name' => 'view elternrat', 'guard_name' => 'web', 'module' => 'Elternrat', 'description' => 'Den Elternrat-Bereich einsehen.'],
            ['name' => 'send urgent message', 'guard_name' => 'web', 'module' => 'Nachrichten', 'description' => 'Dringende Nachrichten versenden.'],
            ['name' => 'edit reinigung', 'guard_name' => 'web', 'module' => 'Reinigung', 'description' => 'Reinigungspläne bearbeiten.'],
            ['name' => 'upload great files', 'guard_name' => 'web', 'module' => 'Inhalte allgemein', 'description' => 'Große Dateien hochladen.'],
            ['name' => 'edit termin', 'guard_name' => 'web', 'module' => 'Termine', 'description' => 'Termine bearbeiten.'],
            ['name' => 'edit terminliste', 'guard_name' => 'web', 'module' => 'Listen', 'description' => 'Terminlisten bearbeiten.'],
            ['name' => 'create terminliste', 'guard_name' => 'web', 'module' => 'Listen', 'description' => 'Terminlisten erstellen.'],
            ['name' => 'view protected', 'guard_name' => 'web', 'module' => 'Inhalte allgemein', 'description' => 'Geschützte Inhalte einsehen.'],
            ['name' => 'add changelog', 'guard_name' => 'web', 'module' => 'Changelog', 'description' => 'Änderungsprotokolle ergänzen.'],
            ['name' => 'edit changelog', 'guard_name' => 'web', 'module' => 'Changelog', 'description' => 'Änderungsprotokolle bearbeiten.'],
            ['name' => 'set password', 'guard_name' => 'web', 'module' => 'Benutzerverwaltung', 'description' => 'Passwörter für Benutzer setzen.'],
            ['name' => 'make sticky', 'guard_name' => 'web', 'module' => 'Nachrichten', 'description' => 'Beiträge anheften.'],
            ['name' => 'view reinigung', 'guard_name' => 'web', 'module' => 'Reinigung', 'description' => 'Reinigungspläne einsehen.'],
            ['name' => 'delete elternrat file', 'guard_name' => 'web', 'module' => 'Elternrat', 'description' => 'Dateien im Elternrat-Bereich löschen.'],
            ['name' => 'view rueckmeldungen', 'guard_name' => 'web', 'module' => 'Verwaltung', 'description' => 'Rückmeldungen einsehen.'],
            ['name' => 'download schickzeiten', 'guard_name' => 'web', 'module' => 'Care', 'description' => 'Schickzeiten exportieren oder herunterladen.'],
            ['name' => 'edit schickzeiten', 'guard_name' => 'web', 'module' => 'Care', 'description' => 'Schickzeiten bearbeiten und Anwesenheiten verwalten.'],
            ['name' => 'view schickzeiten', 'guard_name' => 'web', 'module' => 'Care', 'description' => 'Schickzeiten einsehen. (Sorgeberechtigte)'],
            ['name' => 'manage late pickups', 'guard_name' => 'web', 'module' => 'Care', 'description' => 'Verspätete Abholungen bestätigen oder verwerfen.'],
            ['name' => 'view krankmeldung', 'guard_name' => 'web', 'module' => 'Krankmeldungen', 'description' => 'Krankmeldungen einsehen.'],
            ['name' => 'view groups', 'guard_name' => 'web', 'module' => 'Gruppen', 'description' => 'Gruppen einsehen.'],
            ['name' => 'view mitarbeiterboard', 'guard_name' => 'web', 'module' => 'Links', 'description' => 'Mitarbeiterboard einsehen.'],
            ['name' => 'loginAsUser', 'guard_name' => 'web', 'module' => 'Benutzerverwaltung', 'description' => 'Als anderer Benutzer anmelden.'],
            ['name' => 'create polls', 'guard_name' => 'web', 'module' => 'Nachrichten', 'description' => 'Umfragen erstellen.'],
            ['name' => 'delete groups', 'guard_name' => 'web', 'module' => 'Gruppen', 'description' => 'Gruppen löschen.'],
            ['name' => 'see mails', 'guard_name' => 'web', 'module' => 'Kontakt', 'description' => 'E-Mails einsehen.'],
            ['name' => 'manage rueckmeldungen', 'guard_name' => 'web', 'module' => 'Verwaltung', 'description' => 'Rückmeldungen verwalten.'],
            ['name' => 'assign roles to users', 'guard_name' => 'web', 'module' => 'Benutzerverwaltung', 'description' => 'Rollen Benutzern zuweisen.'],
            ['name' => 'role is assignable', 'guard_name' => 'web', 'module' => 'Benutzerverwaltung', 'description' => 'Rolle für Benutzerzuweisung freigeben.'],
            ['name' => 'edit groups', 'guard_name' => 'web', 'module' => 'Gruppen', 'description' => 'Gruppen bearbeiten.'],
            ['name' => 'view external offer', 'guard_name' => 'web', 'module' => 'Nachrichten', 'description' => 'Externe Angebote anzeigen.'],
            ['name' => 'push to wordpress', 'guard_name' => 'web', 'module' => 'Nachrichten', 'description' => 'Inhalte nach WordPress übertragen.'],
            ['name' => 'download krankmeldungen', 'guard_name' => 'web', 'module' => 'Krankmeldungen', 'description' => 'Krankmeldungen herunterladen.'],
            ['name' => 'allow password-less-login', 'guard_name' => 'web', 'module' => 'Benutzerverwaltung', 'description' => 'Passwortlosen Login erlauben.'],
            ['name' => 'create own group', 'guard_name' => 'web', 'module' => 'Gruppen', 'description' => 'Eigene Gruppen erstellen.'],
            ['name' => 'show in contact form', 'guard_name' => 'web', 'module' => 'Kontakt', 'description' => 'Im Kontaktformular angezeigt werden.'],
            ['name' => 'manage diseases', 'guard_name' => 'web', 'module' => 'Krankmeldungen', 'description' => 'meldepflichtige Erkrankungen verwalten.'],
            ['name' => 'see diseases', 'guard_name' => 'web', 'module' => 'Krankmeldungen', 'description' => 'meldepflichtige Erkrankungen sehen.'],
            ['name' => 'delete posts', 'guard_name' => 'web', 'module' => 'Nachrichten', 'description' => 'Beiträge löschen.'],
            ['name' => 'see logs', 'guard_name' => 'web', 'module' => 'System', 'description' => 'System-Logs einsehen.'],
            ['name' => 'scan files', 'guard_name' => 'web', 'module' => 'System', 'description' => 'Dateien aufräumen.'],
            ['name' => 'delete logs', 'guard_name' => 'web', 'module' => 'System', 'description' => 'System-Logs löschen.'],
            ['name' => 'view sites', 'guard_name' => 'web', 'module' => 'Seiten', 'description' => 'Seiten einsehen.'],
            ['name' => 'create sites', 'guard_name' => 'web', 'module' => 'Seiten', 'description' => 'Seiten erstellen.'],
            ['name' => 'view stundenplan', 'guard_name' => 'web', 'module' => 'Stundenplan', 'description' => 'Stundenplan einsehen.'],
            ['name' => 'view stundenplan teacher', 'guard_name' => 'web', 'module' => 'Stundenplan', 'description' => 'Lehrer-Stundenpläne einsehen.'],
            ['name' => 'view stundenplan room', 'guard_name' => 'web', 'module' => 'Stundenplan', 'description' => 'Raum-Stundenpläne einsehen.'],
            ['name' => 'edit stundenplan', 'guard_name' => 'web', 'module' => 'Stundenplan', 'description' => 'Stundenpläne bearbeiten.'],
            ['name' => 'view vertretungsplan', 'guard_name' => 'web', 'module' => 'Vertretungsplan', 'description' => 'Vertretungsplan einsehen.'],
            ['name' => 'view vertretungsplan all', 'guard_name' => 'web', 'module' => 'Vertretungsplan', 'description' => 'Vertretungsplan für alle Klassen einsehen.'],
            ['name' => 'edit GTA', 'guard_name' => 'web', 'module' => 'Arbeitsgemeinschaften', 'description' => 'Arbeitsgemeinschaften verwalten.'],
            ['name' => 'view GTA', 'guard_name' => 'web', 'module' => 'Arbeitsgemeinschaften', 'description' => 'Arbeitsgemeinschaften einsehen.'],
            ['name' => 'view Pflichtstunden', 'guard_name' => 'web', 'module' => 'Pflichtstunden', 'description' => 'Pflichtstunden einsehen und anzeigen. (Familien)'],
            ['name' => 'edit Pflichtstunden', 'guard_name' => 'web', 'module' => 'Pflichtstunden', 'description' => 'Pflichtstunden verwalten und bestätigen'],
            ['name' => 'schoolyear.change', 'guard_name' => 'web', 'module' => 'Einstellungen', 'description' => 'Schuljahrwechsel durchführen.'],
            ['name' => 'use messenger', 'guard_name' => 'web', 'module' => 'Messanger', 'description' => 'Eltern-Nachrichten verwenden.'],
            ['name' => 'moderate messages', 'guard_name' => 'web', 'module' => 'Messanger', 'description' => 'Nachrichten moderieren.'],
            ['name' => 'use support widget', 'guard_name' => 'web', 'module' => 'Support', 'description' => 'Support-Widget öffnen und nutzen.'],
            ['name' => 'create support ticket', 'guard_name' => 'web', 'module' => 'Support', 'description' => 'Support-Tickets erstellen.'],
            ['name' => 'use messenger', 'guard_name' => 'api', 'module' => 'Messanger', 'description' => 'Eltern-Nachrichten per API verwenden.'],
            ['name' => 'moderate messages', 'guard_name' => 'api', 'module' => 'Messanger', 'description' => 'Nachrichten per API moderieren.'],
            ['name' => 'use support widget', 'guard_name' => 'api', 'module' => 'Support', 'description' => 'Support-Widget per API nutzen.'],
            ['name' => 'create support ticket', 'guard_name' => 'api', 'module' => 'Support', 'description' => 'Support-Tickets per API erstellen.'],
            ['name' => 'create termine', 'guard_name' => 'web', 'module' => 'Termine', 'description' => 'Einzelne Termine erstellen.'],
            ['name' => 'edit settings', 'guard_name' => 'web', 'module' => 'Einstellungen', 'description' => 'Systemeinstellungen und Module verwalten.'],
            ['name' => 'view child', 'guard_name' => 'web', 'module' => 'Care', 'description' => 'Kindbezogene Daten einsehen.'],
            ['name' => 'view listen', 'guard_name' => 'web', 'module' => 'Listen', 'description' => 'Listen einsehen.'],
            ['name' => 'testing', 'guard_name' => 'web', 'module' => 'System', 'description' => 'Interne Testberechtigung.'],
        ];
    }
}

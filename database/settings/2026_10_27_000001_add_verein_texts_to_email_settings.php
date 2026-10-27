<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('email.verein_access_notice', 'Dieser Zugang wird Ihnen als Vereinsmitglied zur Verfügung gestellt, um Sie über Aktuelles aus dem Schulzentrum zu informieren und Ihnen Einladungen zur Mitgliederversammlung zu senden.');
        $this->migrator->add('email.verein_added_text', 'Ihr bestehendes Benutzerkonto wurde als Vereinsmitglied freigeschaltet. Sie erhalten darüber ab sofort Informationen über Aktuelles aus dem Schulzentrum sowie Einladungen zur Mitgliederversammlung. Ihre Zugangsdaten bleiben unverändert.');
        $this->migrator->add('email.verein_removed_text', 'Laut der aktuellen Mitgliederliste sind Sie nicht mehr Mitglied im Verein. Die Vereinsinformationen wurden daher für Ihr Benutzerkonto deaktiviert. Ihr Konto selbst bleibt bestehen. Falls das nicht zutrifft, melden Sie sich bitte bei uns.');
    }

    public function down(): void
    {
        $this->migrator->delete('email.verein_access_notice');
        $this->migrator->delete('email.verein_added_text');
        $this->migrator->delete('email.verein_removed_text');
    }
};

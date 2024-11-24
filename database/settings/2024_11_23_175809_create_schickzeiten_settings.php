<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('schicken.schicken_ab', '08:00');
        $this->migrator->add('schicken.schicken_bis', '17:00');
        $this->migrator->add('schicken.schicken_text', '
            Die angegebenen Zeiten gelten als regelmäßige Schickzeiten, zu denen das Kind jede Woche den Hort verlassen darf.

            Sie können für Ihr Kind auch die Zeit angeben, ab der es frühestens gehen darf. Wir werden das Kind dann nicht losschicken, es aber gehen lassen, wenn es sich verabschiedet.
            Dabei können Sie auch angeben, wann Ihr Kind spätestens den Hort verlassen soll. Die Zeit für "spätestens" wirkt somit nur in Verbindung mit der Angabe "ab".

            Die Liste wird jeweils montags aktualisiert. Gewünschte Änderungen müssen daher bis Montag 8.00 Uhr eingetragen sein.
           ');
        $this->migrator->add('schicken.schicken_intervall', 30);

    }
};

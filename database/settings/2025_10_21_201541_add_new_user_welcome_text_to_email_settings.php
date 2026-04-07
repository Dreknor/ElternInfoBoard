<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        try {
            $this->migrator->add('email.new_user_welcome_text', 'wir freuen uns, Sie bei '.config('app.name').' begrüßen zu dürfen! Ihr Benutzerkonto wurde erfolgreich eingerichtet. Nachfolgend finden Sie Ihre persönlichen Zugangsdaten:');
        } catch (\Throwable $th) {
            return;
        }

    }

    public function down(): void
    {
        $this->migrator->delete('email.new_user_welcome_text');
    }
};

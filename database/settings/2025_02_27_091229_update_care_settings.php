<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('care.hide_groups_when_empty', true );
        $this->migrator->add('care.show_message_on_empty_group', true );
    }
};

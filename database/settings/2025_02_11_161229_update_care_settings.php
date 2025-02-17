<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('care.groups_list', []);
        $this->migrator->add('care.class_list', []);

    }
};

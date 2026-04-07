<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('care.end_time', null);
        $this->migrator->add('care.info_to', null);
    }
};

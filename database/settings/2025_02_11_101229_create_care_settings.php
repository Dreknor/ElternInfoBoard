<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('care.view_detailed_care', false);
        $this->migrator->add('care.hide_childs_when_absent', false);

    }
};

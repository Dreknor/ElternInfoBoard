<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('general.app_name', env('APP_NAME', "ElternInfoApp"));
        $this->migrator->add('general.app_url', env('APP_URL', "http://localhost"));
        $this->migrator->add('general.app_env', env('APP_ENV', "local"));
        $this->migrator->add('general.logo', "logo.png");
        $this->migrator->add('general.favicon', "app_logo.png");


    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('messenger.auto_delete_days', 90);
        $this->migrator->add('messenger.max_message_length', 2000);
        $this->migrator->add('messenger.allow_direct_messages', true);
        $this->migrator->add('messenger.allow_file_uploads', true);
        $this->migrator->add('messenger.max_file_size_mb', 10);
    }
};


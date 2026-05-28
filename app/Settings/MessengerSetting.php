<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class MessengerSetting extends Settings
{
    public int $auto_delete_days;
    public int $max_message_length;
    public bool $allow_direct_messages;
    public bool $allow_file_uploads;
    public int $max_file_size_mb;

    public static function group(): string
    {
        return 'messenger';
    }
}


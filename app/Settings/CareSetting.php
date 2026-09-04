<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CareSetting extends Settings
{
    public bool $view_detailed_care;

    public bool $hide_childs_when_absent;

    public array $groups_list;

    public array $class_list;

    public bool $hide_groups_when_empty;

    public bool $show_message_on_empty_group;

    public ?string $end_time;

    public ?int $info_to;

    public bool $mandate_notification_enabled;

    public ?string $mandate_notification_email;

    public bool $show_mandates;

    public bool $show_parents;

    public string $bundesland;

    public bool $auto_checkin_enabled_schulzeit;

    public string $auto_checkin_time_schulzeit;

    public bool $auto_checkin_enabled_ferien;

    public string $auto_checkin_time_ferien;

    public static function group(): string
    {
        return 'Care';
    }
}

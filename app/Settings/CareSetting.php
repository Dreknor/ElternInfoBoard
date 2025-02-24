<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CareSetting extends Settings
{

    public bool $view_detailed_care;

    public bool $hide_childs_when_absent;

    public array $groups_list;
    public array $class_list;

    public static function group(): string
    {
        return 'Care';
    }
}

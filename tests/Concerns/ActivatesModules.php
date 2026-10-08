<?php

namespace Tests\Concerns;

use App\Model\Module;

/**
 * Aktiviert Module (`modules.setting`) für Tests von Routen hinter `module:<Name>`.
 */
trait ActivatesModules
{
    protected function activateModule(string $setting, array $rights = []): Module
    {
        return Module::updateOrCreate(
            ['setting' => $setting],
            [
                'category' => 'module',
                'description' => $setting,
                'options' => ['active' => '1', 'rights' => $rights],
            ]
        );
    }
}

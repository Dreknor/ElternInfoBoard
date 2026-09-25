<?php

namespace App\Services\App;

use App\Model\Module;
use App\Model\User;

/**
 * Aktive Module, die der Nutzer sehen darf (gleiche Regeln wie `GET /api/modules`).
 */
class Modules
{
    /** @return string[] Namen (`modules.setting`) */
    public static function activeFor(User $user): array
    {
        return Module::where('category', 'module')->get()
            ->filter(function (Module $module) use ($user) {
                $active = $module->options['active'] ?? false;
                if (! ($active === true || $active === 1 || $active === '1')) {
                    return false;
                }
                $rights = $module->options['rights'] ?? [];
                if (is_string($rights)) {
                    $rights = json_decode($rights, true) ?? [];
                }

                return empty($rights) || collect($rights)->contains(fn ($right) => $user->can($right));
            })
            ->pluck('setting')
            ->values()
            ->all();
    }
}

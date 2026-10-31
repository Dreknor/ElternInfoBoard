<?php

namespace App\Services;

use App\Model\User;
use App\Model\UserAppSettings;
use App\Settings\GeneralSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Seitenmenü: Auflösung der Darstellung (klassisch/kompakt) und Aufbau
 * des gruppierten Menüs für die kompakte Darstellung.
 */
class NavigationService
{
    public const STYLE_CLASSIC = 'classic';

    public const STYLE_COMPACT = 'compact';

    public function __construct(private GeneralSetting $generalSetting) {}

    /**
     * @return array<string, string> Darstellung => Beschriftung
     */
    public function styles(): array
    {
        return config('navigation.styles', [
            self::STYLE_CLASSIC => 'Klassisch',
            self::STYLE_COMPACT => 'Kompakt',
        ]);
    }

    public function isValidStyle(?string $style): bool
    {
        return is_string($style) && array_key_exists($style, $this->styles());
    }

    public function defaultStyle(): string
    {
        try {
            $style = $this->generalSetting->nav_style ?? self::STYLE_CLASSIC;
        } catch (\Throwable) {
            $style = self::STYLE_CLASSIC;
        }

        return $this->isValidStyle($style) ? $style : self::STYLE_CLASSIC;
    }

    public function allowUserStyle(): bool
    {
        try {
            return (bool) ($this->generalSetting->allow_user_nav_style ?? true);
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * Gespeicherte Nutzerwahl ('' = System-Standard).
     */
    public function userStyle(?User $user): string
    {
        if (! $user) {
            return '';
        }

        $style = data_get(UserAppSettings::where('user_id', $user->id)->first()?->settings, 'nav_style');

        return $this->isValidStyle($style) ? $style : '';
    }

    /**
     * Aktive Darstellung: Nutzerwahl (falls erlaubt) > System-Standard.
     */
    public function resolveStyle(?User $user): string
    {
        if ($this->allowUserStyle()) {
            $userStyle = $this->userStyle($user);
            if ($userStyle !== '') {
                return $userStyle;
            }
        }

        return $this->defaultStyle();
    }

    /**
     * Baut das gruppierte Menü aus den aktiven Modulen.
     *
     * @return array{top: array, groups: array, admin: array, admin_top: array}
     */
    public function build(User $user, Collection $modules, Collection $notifications, string $path): array
    {
        $path = trim($path, '/');
        $unread = $notifications->where('read', 0);

        $top = [];
        $groups = $this->emptyGroups(config('navigation.groups', []));
        $adminGroups = $this->emptyGroups(config('navigation.admin_groups', []));

        foreach ($modules as $module) {
            $options = $module->options ?? [];

            $nav = $options['nav'] ?? null;
            if (is_array($nav) && isset($nav['name'], $nav['link'], $nav['icon']) && $this->canSeeModule($user, $options)) {
                $item = $this->makeItem($nav, $path);
                $item['unread'] = $unread->where('type', $nav['name'])->count();

                $groupKey = $this->groupFor($module->setting, $nav, 'modules', 'fallback_group', $groups);
                if ($groupKey === 'top') {
                    $top[] = $item;
                } else {
                    $groups[$groupKey]['items'][] = $item;
                }
            }

            $admNav = $options['adm-nav'] ?? null;
            if (is_array($admNav) && isset($admNav['name'], $admNav['link'], $admNav['adm-rights'])
                && $this->canSeeAdmin($user, (array) $admNav['adm-rights'])) {
                $item = $this->makeItem($admNav, $path);
                $item['unread'] = 0;

                $groupKey = $this->groupFor($module->setting, $admNav, 'admin_modules', 'admin_fallback_group', $adminGroups);
                $adminGroups[$groupKey]['items'][] = $item;
            }
        }

        // Nur der spezifischste Treffer gilt als aktiv (z. B. 'termine/create' vor 'termine')
        $best = 0;
        $all = array_merge($top, ...array_column($groups, 'items'), ...array_column($adminGroups, 'items'));
        foreach ($all as $item) {
            $best = max($best, $item['match']);
        }
        $markActive = function (array $items) use ($best): array {
            return array_map(function (array $item) use ($best) {
                $item['active'] = $best > 0 && $item['match'] === $best;
                unset($item['match']);

                return $item;
            }, $items);
        };
        $top = $markActive($top);
        foreach ($groups as $key => $group) {
            $groups[$key]['items'] = $markActive($group['items']);
        }
        foreach ($adminGroups as $key => $group) {
            $adminGroups[$key]['items'] = $markActive($group['items']);
        }

        [$groups, $singles] = $this->finalizeGroups($groups);
        [$adminGroups, $adminSingles] = $this->finalizeGroups($adminGroups);

        return [
            'top' => array_merge($top, $singles),
            'groups' => $groups,
            'admin_top' => $adminSingles,
            'admin' => $adminGroups,
        ];
    }

    private function emptyGroups(array $definitions): array
    {
        $groups = [];
        foreach ($definitions as $key => $definition) {
            $groups[$key] = [
                'key' => $key,
                'label' => $definition['label'] ?? Str::headline($key),
                'icon' => $definition['icon'] ?? 'fas fa-folder',
                'items' => [],
            ];
        }

        return $groups;
    }

    /**
     * Leere Gruppen entfernen, Gruppen mit nur einem Eintrag auflösen
     * und Kennzahlen (aktiv, ungelesen) berechnen.
     *
     * @return array{0: array, 1: array} [Gruppen, Einzeleinträge]
     */
    private function finalizeGroups(array $groups): array
    {
        $result = [];
        $singles = [];

        foreach ($groups as $group) {
            $count = count($group['items']);
            if ($count === 0) {
                continue;
            }
            if ($count === 1) {
                $singles[] = $group['items'][0];

                continue;
            }

            $group['active'] = collect($group['items'])->contains('active', true);
            $group['unread'] = collect($group['items'])->sum('unread');
            $result[] = $group;
        }

        return [$result, $singles];
    }

    private function groupFor(?string $moduleName, array $nav, string $mapKey, string $fallbackKey, array $groups): string
    {
        // Kein Dot-Pfad: Modulnamen können Punkte enthalten (z. B. 'meldepfl. Erkrankungen')
        $group = $nav['group'] ?? (config("navigation.{$mapKey}", [])[$moduleName ?? ''] ?? null);

        if ($group === 'top' && $mapKey === 'modules') {
            return 'top';
        }

        if (is_string($group) && array_key_exists($group, $groups)) {
            return $group;
        }

        $fallback = config("navigation.{$fallbackKey}");

        return array_key_exists($fallback, $groups) ? $fallback : array_key_last($groups);
    }

    private function makeItem(array $nav, string $path): array
    {
        $link = (string) $nav['link'];
        $external = Str::startsWith($link, ['http://', 'https://']);
        $linkPath = trim($link, '/');

        return [
            'name' => (string) $nav['name'],
            'icon' => (string) ($nav['icon'] ?? 'fas fa-circle'),
            'url' => url($link),
            'external' => $external,
            // Länge des passenden Pfads (0 = kein Treffer), ausgewertet in build()
            'match' => ! $external && $linkPath !== ''
                && ($path === $linkPath || Str::startsWith($path, $linkPath.'/'))
                ? strlen($linkPath) : 0,
        ];
    }

    private function canSeeModule(User $user, array $options): bool
    {
        $rights = $options['rights'] ?? [];
        if (is_string($rights)) {
            $rights = json_decode($rights, true) ?? [];
        }
        if (! is_array($rights)) {
            $rights = array_values((array) $rights);
        }

        return count($rights) === 0 || $user->hasAnyPermission($rights);
    }

    private function canSeeAdmin(User $user, array $rights): bool
    {
        foreach ($rights as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}

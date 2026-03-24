<?php

namespace App\Services;

class UserAppSettingsService
{
    /**
     * Get the default settings structure.
     *
     * @return array
     */
    public static function getDefaultSettings(): array
    {
        return [
            'version' => '1.0',
            'theme' => [
                'mode' => 'light',
                'compact_view' => false,
            ],
            'navigation' => [
                'footer_items' => [
                    [
                        'id' => 'dashboard',
                        'label' => 'Dashboard',
                        'icon' => 'home',
                        'route' => 'dashboard',
                        'order' => 1,
                        'visible' => true,
                    ],
                    [
                        'id' => 'timetable',
                        'label' => 'Stundenplan',
                        'icon' => 'calendar',
                        'route' => 'timetable',
                        'order' => 2,
                        'visible' => true,
                    ],
                    [
                        'id' => 'termine',
                        'label' => 'Termine',
                        'icon' => 'calendar-event',
                        'route' => 'termine',
                        'order' => 3,
                        'visible' => true,
                    ],
                    [
                        'id' => 'messages',
                        'label' => 'Nachrichten',
                        'icon' => 'mail',
                        'route' => 'messages',
                        'order' => 4,
                        'visible' => true,
                    ],
                    [
                        'id' => 'care',
                        'label' => 'Care',
                        'icon' => 'users',
                        'route' => 'care',
                        'order' => 5,
                        'visible' => true,
                    ],
                ],
                'start_page' => 'dashboard',
            ],
            'dashboard' => [
                'widgets' => [
                    [
                        'id' => 'losung',
                        'label' => 'Losung des Tages',
                        'visible' => true,
                        'order' => 1,
                    ],
                    [
                        'id' => 'diseases',
                        'label' => 'Aktive Erkrankungen',
                        'visible' => true,
                        'order' => 2,
                    ],
                    [
                        'id' => 'care_status',
                        'label' => 'Care-Kinder Status',
                        'visible' => true,
                        'order' => 3,
                    ],
                ],
                'quick_access' => [
                    [
                        'id' => 'timetable',
                        'label' => 'Stundenplan ansehen',
                        'route' => 'timetable',
                        'visible' => true,
                        'order' => 1,
                    ],
                    [
                        'id' => 'termine',
                        'label' => 'Termine ansehen',
                        'route' => 'termine',
                        'visible' => true,
                        'order' => 2,
                    ],
                    [
                        'id' => 'listen',
                        'label' => 'Listen & Eintragungen',
                        'route' => 'listen',
                        'visible' => true,
                        'order' => 3,
                    ],
                    [
                        'id' => 'pflichtstunden',
                        'label' => 'Pflichtstunden',
                        'route' => 'pflichtstunden',
                        'visible' => true,
                        'order' => 4,
                    ],
                    [
                        'id' => 'care',
                        'label' => 'Care-Modul',
                        'route' => 'care',
                        'visible' => true,
                        'order' => 5,
                    ],
                    [
                        'id' => 'messages',
                        'label' => 'Nachrichten',
                        'route' => 'messages',
                        'visible' => true,
                        'order' => 6,
                    ],
                    [
                        'id' => 'krankmeldungen',
                        'label' => 'Krankmeldungen',
                        'route' => 'krankmeldungen',
                        'visible' => true,
                        'order' => 7,
                    ],
                    [
                        'id' => 'files',
                        'label' => 'Dateien & Downloads',
                        'route' => 'files',
                        'visible' => true,
                        'order' => 8,
                    ],
                    [
                        'id' => 'kontakt',
                        'label' => 'Kontakt',
                        'route' => 'kontakt',
                        'visible' => true,
                        'order' => 9,
                    ],
                ],
                'style' => 'expanded',
            ],
            'modules' => [
                'timetable' => [
                    'default_view' => 'week',
                    'highlight_substitutions' => true,
                ],
                'messages' => [
                    'unread_first' => true,
                    'push_notifications' => true,
                ],
                'care' => [
                    'default_tab' => 'children',
                    'show_sick_on_dashboard' => true,
                ],
            ],
        ];
    }

    /**
     * Get valid footer item IDs.
     *
     * @return array
     */
    public static function getValidFooterItemIds(): array
    {
        return [
            'dashboard',
            'timetable',
            'termine',
            'messages',
            'care',
            'listen',
            'pflichtstunden',
            'krankmeldungen',
            'files',
            'kontakt',
        ];
    }

    /**
     * Get valid widget IDs.
     *
     * @return array
     */
    public static function getValidWidgetIds(): array
    {
        return [
            'losung',
            'diseases',
            'care_status',
        ];
    }

    /**
     * Get valid quick access IDs.
     *
     * @return array
     */
    public static function getValidQuickAccessIds(): array
    {
        return [
            'timetable',
            'termine',
            'listen',
            'pflichtstunden',
            'care',
            'messages',
            'krankmeldungen',
            'files',
            'kontakt',
        ];
    }

    /**
     * Validate settings path.
     *
     * @param string $path
     * @return bool
     */
    public static function isValidPath(string $path): bool
    {
        $validPaths = [
            'version',
            'theme',
            'theme.mode',
            'theme.compact_view',
            'navigation',
            'navigation.footer_items',
            'navigation.start_page',
            'dashboard',
            'dashboard.widgets',
            'dashboard.quick_access',
            'dashboard.style',
            'modules',
            'modules.timetable',
            'modules.timetable.default_view',
            'modules.timetable.highlight_substitutions',
            'modules.messages',
            'modules.messages.unread_first',
            'modules.messages.push_notifications',
            'modules.care',
            'modules.care.default_tab',
            'modules.care.show_sick_on_dashboard',
        ];

        // Check if path is in valid paths or is a child of an array path
        foreach ($validPaths as $validPath) {
            if ($path === $validPath || str_starts_with($path, $validPath . '.')) {
                return true;
            }
        }

        return false;
    }
}


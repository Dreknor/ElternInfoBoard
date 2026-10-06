<?php

namespace App\Services\Notifications;

use App\Model\NotificationPreference;
use App\Model\User;

/**
 * Lesen und Schreiben der Benachrichtigungskanäle je Nutzer und Kategorie.
 * Ohne gespeicherte Zeile ist jeder Kanal aktiv (bisheriges Verhalten).
 */
class NotificationPreferences
{
    /**
     * Einstellungen für die Anzeige: nur relevante Kategorien, nicht verfügbare Kanäle = null.
     *
     * @return array<int, array{key: string, label: string, description: string, channels: array<string, bool|null>}>
     */
    public static function forUser(User $user): array
    {
        $stored = NotificationPreference::where('user_id', $user->id)->get()->keyBy('category');
        $definitions = NotificationCategory::definitions();

        return array_map(function (string $key) use ($stored, $definitions) {
            $row = $stored->get($key);
            $channels = [];
            foreach (NotificationCategory::CHANNELS as $channel) {
                $channels[$channel] = NotificationCategory::supports($key, $channel)
                    ? (bool) ($row?->{$channel} ?? true)
                    : null;
            }

            return [
                'key' => $key,
                'label' => $definitions[$key]['label'],
                'description' => $definitions[$key]['description'],
                'channels' => $channels,
            ];
        }, NotificationCategory::visibleFor($user));
    }

    /**
     * @param  array<string, array<string, mixed>>  $categories  ['nachrichten' => ['app' => true, 'mail' => false], …]
     */
    public static function update(User $user, array $categories): void
    {
        foreach ($categories as $category => $channels) {
            if (! in_array($category, NotificationCategory::keys(), true) || ! is_array($channels)) {
                continue;
            }
            $values = [];
            foreach (NotificationCategory::CHANNELS as $channel) {
                if (array_key_exists($channel, $channels) && NotificationCategory::supports($category, $channel)) {
                    $values[$channel] = filter_var($channels[$channel], FILTER_VALIDATE_BOOLEAN);
                }
            }
            if ($values) {
                NotificationPreference::updateOrCreate(['user_id' => $user->id, 'category' => $category], $values);
            }
        }
    }

    /** Einzelnen Kanal setzen (z. B. alte App-Versionen über `/api/user/settings`). */
    public static function set(int $userId, string $category, string $channel, bool $enabled): void
    {
        if (NotificationCategory::supports($category, $channel)) {
            NotificationPreference::updateOrCreate(['user_id' => $userId, 'category' => $category], [$channel => $enabled]);
        }
    }

    public static function allows(User|int $user, string $category, string $channel): bool
    {
        if ($category === NotificationCategory::SYSTEM || ! NotificationCategory::supports($category, $channel)) {
            return true;
        }

        return ! NotificationPreference::where('user_id', $user instanceof User ? $user->id : $user)
            ->where('category', $category)
            ->where($channel, false)
            ->exists();
    }

    /**
     * Nutzer-IDs, die den Kanal für die Kategorie zulassen (eine Abfrage für viele Nutzer).
     *
     * @param  int[]  $userIds
     * @return int[]
     */
    public static function filter(array $userIds, string $category, string $channel): array
    {
        if ($userIds === [] || $category === NotificationCategory::SYSTEM || ! NotificationCategory::supports($category, $channel)) {
            return array_values($userIds);
        }

        $optedOut = [];
        foreach (array_chunk(array_values(array_unique($userIds)), 1000) as $chunk) {
            $optedOut = array_merge($optedOut, NotificationPreference::whereIn('user_id', $chunk)
                ->where('category', $category)
                ->where($channel, false)
                ->pluck('user_id')
                ->all());
        }

        return array_values(array_diff($userIds, $optedOut));
    }
}

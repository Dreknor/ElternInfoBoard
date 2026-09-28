<?php

namespace App\Traits;

use App\Model\Notification;
use App\Services\Push\NativePushService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

trait NotificationTrait
{
    /**
     * @param bool $updateExisting  Wenn true: bestehende ungelesene Benachrichtigungen
     *                              mit derselben URL/Typ werden aktualisiert statt übersprungen.
     *                              Sinnvoll z. B. für den Messenger, damit die neueste Nachricht
     *                              immer in der Glocke erscheint.
     */
    public function notify(Collection $users, string $title, string $message, bool $important = false, ?string $url = null, string $type = 'info', string $icon = '', bool $updateExisting = false): void
    {
        if ($users->isEmpty()) {
            return;
        }

        $usersToCreate = $users;

        if ($url !== null) {
            // Nutzer ermitteln, die bereits eine ungelesene Benachrichtigung haben.
            $existingNotifications = Notification::where('type', $type)
                ->where('url', $url)
                ->where('read', false)
                ->whereIn('user_id', $users->pluck('id'))
                ->get()
                ->keyBy('user_id');

            $alreadyNotifiedIds = $existingNotifications->keys()->flip();

            if ($updateExisting && $existingNotifications->isNotEmpty()) {
                // Bestehende ungelesene Benachrichtigungen auf neuesten Stand bringen.
                Notification::where('type', $type)
                    ->where('url', $url)
                    ->where('read', false)
                    ->whereIn('user_id', $alreadyNotifiedIds->keys()->all())
                    ->update([
                        'title'      => $title,
                        'message'    => $message,
                        'updated_at' => now(),
                    ]);

                // Weitere Chat-Nachricht o. Ä.: nativen Push höchstens alle 10 Minuten je Nutzer/Ziel.
                $pushAgain = $alreadyNotifiedIds->keys()
                    ->filter(fn ($id) => Cache::add("native_push_{$id}_".md5($url), true, now()->addMinutes(10)))
                    ->all();
                NativePushService::dispatch($pushAgain, $title, $message, $url, $type);
            }

            // Nur Nutzer ohne bestehende Benachrichtigung neu anlegen.
            $usersToCreate = $users->filter(fn ($user) => ! $alreadyNotifiedIds->has($user->id));
        }

        if ($usersToCreate->isEmpty()) {
            return;
        }

        $notifications = [];
        $usersToCreate->each(function ($user) use ($title, $message, $url, $type, $icon, $important, &$notifications) {
            $notifications[] = [
                'user_id'    => $user->id,
                'title'      => $title,
                'message'    => $message,
                'url'        => $url,
                'type'       => $type,
                'icon'       => $icon,
                'read'       => false,
                'important'  => $important,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        if (! empty($notifications)) {
            Notification::insert($notifications);
            // insert() löst keine Model-Events aus → nativen Push (Eltern-App) hier anstoßen.
            NativePushService::dispatch(array_column($notifications, 'user_id'), $title, $message, $url, $type);
        }
    }
}

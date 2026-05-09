<?php

namespace App\Traits;

use App\Model\Notification;
use Illuminate\Database\Eloquent\Collection;

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
        }
    }
}

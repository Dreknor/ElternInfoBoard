<?php

namespace App\Services\Push;

use App\Model\User;
use App\Model\UserDevice;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Browser-Abos (Web-Push) eines Nutzers, ohne die Geräte, auf denen bereits die App Push erhält.
 *
 * Wer im mobilen Browser Push aktiviert hat und später die App installiert, hätte sonst
 * jede Mitteilung doppelt. Ein Browser-Abo gilt als von der App abgedeckt, wenn die App
 * auf derselben Plattform registriert ist (iOS ↔ APNs, Android ↔ FCM). Ältere Abos ohne
 * bekannte Plattform werden dann ebenfalls übersprungen; sie erhalten ihre Plattform beim
 * nächsten Besuch der Seite (das Abo wird still erneuert), Desktop-Browser bekommen danach
 * wieder Push.
 */
class WebPushRouting
{
    public static function subscriptionsFor(User $user): EloquentCollection
    {
        $subscriptions = $user->pushSubscriptions;
        if ($subscriptions->isEmpty()) {
            return $subscriptions;
        }

        $appPlatforms = self::appPlatforms($user);

        return $subscriptions->reject(fn ($subscription) => self::coveredByApp($subscription, $appPlatforms))->values();
    }

    /** Plattformen, auf denen der Nutzer die App mit Push registriert hat. */
    public static function appPlatforms(User $user): Collection
    {
        return UserDevice::where('user_id', $user->id)->pluck('provider')
            ->map(fn (string $provider) => $provider === UserDevice::PROVIDER_APNS ? BrowserDevice::IOS : BrowserDevice::ANDROID)
            ->unique()
            ->values();
    }

    public static function coveredByApp($subscription, Collection $appPlatforms): bool
    {
        return $appPlatforms->isNotEmpty()
            && ($subscription->platform === null || $appPlatforms->contains($subscription->platform));
    }
}

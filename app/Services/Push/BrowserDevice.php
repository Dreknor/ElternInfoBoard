<?php

namespace App\Services\Push;

/**
 * Erkennt Plattform und lesbaren Gerätenamen eines Browsers (für Web-Push-Abos).
 */
class BrowserDevice
{
    public const IOS = 'ios';

    public const ANDROID = 'android';

    public const DESKTOP = 'desktop';

    public const PLATFORMS = [self::IOS, self::ANDROID, self::DESKTOP];

    /** Plattform aus dem Hinweis des Browsers (erkennt auch iPads), sonst aus dem User-Agent. */
    public static function platform(?string $hint, ?string $userAgent): string
    {
        if (in_array($hint, self::PLATFORMS, true)) {
            return $hint;
        }

        $userAgent ??= '';
        if (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            return self::IOS;
        }
        if (stripos($userAgent, 'Android') !== false) {
            return self::ANDROID;
        }

        return self::DESKTOP;
    }

    /** z. B. „Chrome auf Android“, „Safari auf iPhone“, „Firefox auf Windows“. */
    public static function name(?string $userAgent, string $platform): string
    {
        $userAgent ??= '';

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') || str_contains($userAgent, 'EdgiOS') => 'Edge',
            str_contains($userAgent, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Firefox') || str_contains($userAgent, 'FxiOS') => 'Firefox',
            str_contains($userAgent, 'Chrome') || str_contains($userAgent, 'CriOS') => 'Chrome',
            str_contains($userAgent, 'Safari') => 'Safari',
            default => 'Browser',
        };

        $system = match (true) {
            str_contains($userAgent, 'iPad') => 'iPad',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPod') => 'iPhone',
            $platform === self::IOS => 'iPad',
            $platform === self::ANDROID || str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS X') || str_contains($userAgent, 'Macintosh') => 'Mac',
            str_contains($userAgent, 'CrOS') => 'ChromeOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => null,
        };

        return $system ? "{$browser} auf {$system}" : $browser;
    }
}

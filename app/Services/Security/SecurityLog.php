<?php

namespace App\Services\Security;

use App\Model\SecurityEvent;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Datensparsames Sicherheitsprotokoll (Art. 32 DSGVO / § 27 DSG-EKD).
 * Wirft niemals eine Exception, damit Login/Download nie am Protokoll scheitern.
 */
class SecurityLog
{
    /** Schlüsselbestandteile (case-insensitive), deren Werte nie gespeichert werden. */
    private const FORBIDDEN_KEYS = ['password', 'passwort', 'token', 'secret', 'code', 'key', 'signature'];

    private const MAX_KEYS = 20;

    private const MAX_STRING = 255;

    public static function record(string $event, ?int $userId = null, array $context = []): void
    {
        if (! config('security_log.enabled')) {
            return;
        }

        try {
            $userId ??= auth()->id();
            $hasRequest = ! app()->runningInConsole() || request()->server('REMOTE_ADDR') !== null;

            $userAgent = $hasRequest ? request()->userAgent() : null;

            SecurityEvent::create([
                'event' => mb_substr($event, 0, 64),
                'user_id' => $userId,
                'actor_id' => self::resolveActorId($userId),
                'ip_address' => $hasRequest ? request()->ip() : null,
                'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 255) : null,
                'context' => self::sanitizeContext($context) ?: null,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Bewusst ohne Kontextdaten protokollieren
            Log::error('Sicherheitsprotokoll konnte nicht geschrieben werden: '.get_class($e));
        }
    }

    /** Kurzer, stabiler HMAC-Hash einer Kennung – ohne Klartext korrelierbar. */
    public static function identifierHash(string $identifier): string
    {
        return substr(hash_hmac('sha256', mb_strtolower(trim($identifier)), (string) config('app.key')), 0, 16);
    }

    private static function resolveActorId(?int $userId): ?int
    {
        $own = session('ownID');
        if ($own !== null && $own !== '') {
            // Die App legt die ID bei Impersonation verschlüsselt ab (UserController::loginAsUser)
            try {
                $own = Crypt::decryptString($own);
            } catch (Throwable) {
                // unverschlüsselter Wert: unverändert verwenden
            }
            if (is_numeric($own)) {
                return (int) $own;
            }
        }

        $authId = auth()->id();

        return ($authId !== null && (int) $authId !== $userId) ? (int) $authId : null;
    }

    private static function sanitizeContext(array $context): array
    {
        $clean = [];
        foreach ($context as $key => $value) {
            if (count($clean) >= self::MAX_KEYS) {
                break;
            }
            if (self::isForbiddenKey((string) $key)) {
                continue;
            }
            if (is_array($value)) {
                $sub = [];
                foreach ($value as $subKey => $subValue) {
                    if (! is_scalar($subValue) && $subValue !== null) {
                        continue;
                    }
                    if (self::isForbiddenKey((string) $subKey)) {
                        continue;
                    }
                    $sub[$subKey] = self::limit($subValue);
                    if (count($sub) >= self::MAX_KEYS) {
                        break;
                    }
                }
                $clean[$key] = $sub;
            } elseif (is_scalar($value) || $value === null) {
                $clean[$key] = self::limit($value);
            }
        }

        return $clean;
    }

    private static function isForbiddenKey(string $key): bool
    {
        $key = mb_strtolower($key);
        // Reine Verweise/Kennzeichen (token_id, token_type …) sind keine Geheimnisse
        if (preg_match('/_(id|ids|type|count)$/', $key)) {
            return false;
        }
        foreach (self::FORBIDDEN_KEYS as $forbidden) {
            if (str_contains($key, $forbidden)) {
                return true;
            }
        }

        return false;
    }

    private static function limit(mixed $value): mixed
    {
        return is_string($value) ? mb_substr($value, 0, self::MAX_STRING) : $value;
    }
}

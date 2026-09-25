<?php

namespace App\Services\Push;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Apple Push Notification service – direkt per HTTP/2 mit Token-Authentifizierung (ES256-JWT).
 * Keine Zusatzpakete, keine Drittanbieter: nur der .p8-Schlüssel aus dem Apple-Entwicklerkonto.
 */
class ApnsSender
{
    public function isConfigured(): bool
    {
        $c = config('services.apns');

        return ! empty($c['key_id']) && ! empty($c['team_id']) && ! empty($c['key_path'])
            && is_readable(base_path($c['key_path']));
    }

    /**
     * @return bool|null true = zugestellt, false = Token ungültig (löschen), null = anderer Fehler
     */
    public function send(string $token, array $payload): ?bool
    {
        $c = config('services.apns');
        $host = $c['production'] ? 'https://api.push.apple.com' : 'https://api.sandbox.push.apple.com';

        $ch = curl_init("{$host}/3/device/{$token}");
        curl_setopt_array($ch, [
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'authorization: bearer '.$this->jwt(),
                'apns-topic: '.$c['bundle_id'],
                'apns-push-type: alert',
                'apns-priority: 10',
                'content-type: application/json',
            ],
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($status === 200) {
            return true;
        }
        $reason = json_decode((string) $body, true)['reason'] ?? '';
        if ($status === 410 || in_array($reason, ['BadDeviceToken', 'Unregistered', 'DeviceTokenNotForTopic'], true)) {
            return false;
        }
        Log::warning('APNs-Versand fehlgeschlagen', ['status' => $status, 'reason' => $reason]);

        return null;
    }

    /** Das JWT ist bis zu 60 min gültig; Apple verlangt Erneuerung frühestens alle 20 min. */
    private function jwt(): string
    {
        return Cache::remember('apns_jwt', now()->addMinutes(40), function () {
            $c = config('services.apns');
            $header = $this->b64(json_encode(['alg' => 'ES256', 'kid' => $c['key_id']]));
            $claims = $this->b64(json_encode(['iss' => $c['team_id'], 'iat' => time()]));
            $key = openssl_pkey_get_private(file_get_contents(base_path($c['key_path'])));
            openssl_sign("{$header}.{$claims}", $der, $key, OPENSSL_ALGO_SHA256);

            return "{$header}.{$claims}.".$this->b64($this->derToJose($der));
        });
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /** OpenSSL liefert eine DER-Signatur, JWT erwartet r||s (je 32 Byte). */
    private function derToJose(string $der): string
    {
        $offset = 3;
        $rLen = ord($der[$offset]);
        $r = substr($der, $offset + 1, $rLen);
        $offset += $rLen + 2;
        $sLen = ord($der[$offset]);
        $s = substr($der, $offset + 1, $sLen);

        return str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT).str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);
    }
}

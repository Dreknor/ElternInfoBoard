<?php

namespace App\Services\Push;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging (HTTP v1) – kostenlos im Spark-Tarif.
 * Authentifizierung per Service-Account (RS256-JWT → OAuth-Token), ohne Firebase-SDK.
 */
class FcmSender
{
    public function isConfigured(): bool
    {
        $path = config('services.fcm.credentials');

        return ! empty($path) && is_readable(base_path($path));
    }

    /**
     * @return bool|null true = zugestellt, false = Token ungültig (löschen), null = anderer Fehler
     */
    public function send(string $token, string $title, string $body, array $data): ?bool
    {
        $credentials = $this->credentials();
        $response = Http::withToken($this->accessToken())
            ->timeout(10)
            ->post("https://fcm.googleapis.com/v1/projects/{$credentials['project_id']}/messages:send", [
                'message' => [
                    'token' => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    // FCM erlaubt nur String-Werte im data-Block.
                    'data' => array_map('strval', array_filter($data, fn ($v) => $v !== null)),
                    'android' => ['priority' => 'high', 'notification' => ['channel_id' => 'default']],
                ],
            ]);

        if ($response->successful()) {
            return true;
        }
        $code = $response->json('error.details.0.errorCode') ?? $response->json('error.status');
        if ($response->status() === 404 || in_array($code, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
            return false;
        }
        Log::warning('FCM-Versand fehlgeschlagen', ['status' => $response->status(), 'code' => $code]);

        return null;
    }

    private function credentials(): array
    {
        return json_decode(file_get_contents(base_path(config('services.fcm.credentials'))), true);
    }

    private function accessToken(): string
    {
        return Cache::remember('fcm_access_token', now()->addMinutes(50), function () {
            $c = $this->credentials();
            $now = time();
            $header = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->b64(json_encode([
                'iss' => $c['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
            openssl_sign("{$header}.{$claims}", $signature, $c['private_key'], OPENSSL_ALGO_SHA256);

            return Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => "{$header}.{$claims}.".$this->b64($signature),
            ])->throw()->json('access_token');
        });
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

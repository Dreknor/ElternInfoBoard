<?php

namespace App\Services\Security;

use App\Model\User;
use App\Services\IntegrationTokens;
use App\Services\McpTokens;
use App\Support\StudentAccounts;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

/**
 * Token-Lebenszyklus und Folgen einer Passwortänderung für das Sicherheitsprotokoll.
 *
 * Kontext nur mit Verweisen (token_id, token_type, …; SecurityLog lässt Schlüssel auf _id/_ids/_type/_count
 * zu). Tokenwerte und Gerätenamen werden nie protokolliert.
 */
class AccountSecurity
{
    /** app | integration | mcp | student */
    public static function credentialType(mixed $token): string
    {
        if (! $token instanceof PersonalAccessToken) {
            return 'unknown';
        }
        if (McpTokens::isMcp($token)) {
            return 'mcp';
        }
        if (IntegrationTokens::isIntegration($token)) {
            return 'integration';
        }
        if (in_array(StudentAccounts::TOKEN_ABILITY, $token->abilities ?? [], true)) {
            return 'student';
        }

        return 'app';
    }

    /** Ausgabe eines Tokens protokollieren (ohne Tokenwert und Gerätenamen). */
    public static function tokenIssued(NewAccessToken $new, array $context = [], string $event = 'token.issued'): void
    {
        $token = $new->accessToken;

        SecurityLog::record($event, (int) $token->tokenable_id, array_merge([
            'token_id' => $token->id,
            'token_type' => self::credentialType($token),
            'expires_at' => self::expiresAt($token),
        ], $context));
    }

    /** Verlängerung: neues Token ersetzt das bisherige. */
    public static function tokenRefreshed(NewAccessToken $new, mixed $old): void
    {
        self::tokenIssued($new, [
            'previous_token_id' => $old instanceof PersonalAccessToken ? $old->id : null,
        ], 'token.refreshed');
    }

    /** Widerruf eines einzelnen Tokens protokollieren (vor oder nach dem Löschen aufrufen). */
    public static function tokenRevoked(mixed $token, string $reason): void
    {
        if (! $token instanceof PersonalAccessToken) {
            return;
        }

        SecurityLog::record('token.revoked', (int) $token->tokenable_id, [
            'token_ids' => [$token->id],
            'token_type' => self::credentialType($token),
            'count' => 1,
            'reason' => $reason,
        ]);
    }

    /**
     * Alle Tokens eines Kontos widerrufen (optional bis auf eines) und protokollieren.
     *
     * @return int Anzahl widerrufener Tokens
     */
    public static function revokeTokens(User $user, string $reason, ?int $exceptId = null): int
    {
        $ids = $user->tokens()
            ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
            ->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        $user->tokens()->whereIn('id', $ids)->delete();

        SecurityLog::record('token.revoked', $user->id, [
            'token_ids' => $ids->take(20)->all(),
            'count' => $ids->count(),
            'reason' => $reason,
        ]);

        return $ids->count();
    }

    /**
     * Nach einer Passwortänderung: Tokens widerrufen (außer ggf. dem aktuellen), remember_token
     * neu setzen und das Ereignis protokollieren. Andere Web-Sitzungen beendet
     * AuthenticateSession (web-Gruppe) beim nächsten Aufruf.
     */
    public static function passwordChanged(User $user, string $event, ?int $keepTokenId = null, bool $rotateRememberToken = true, array $context = []): void
    {
        $revoked = 0;
        try {
            $revoked = self::revokeTokens($user, 'password_changed', $keepTokenId);

            if ($rotateRememberToken) {
                $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
            }
        } catch (Throwable $e) {
            report($e);
        }

        SecurityLog::record($event, $user->id, array_merge([
            'revoked_token_count' => $revoked,
        ], $context));
    }

    private static function expiresAt(PersonalAccessToken $token): ?string
    {
        if ($token->expires_at) {
            return $token->expires_at->toIso8601String();
        }

        // Integrations-Tokens ohne Ablauf sind unbegrenzt; App-Tokens laufen nach sanctum.expiration ab.
        $minutes = config('sanctum.expiration');
        if (! $minutes || IntegrationTokens::isIntegration($token)) {
            return null;
        }

        return ($token->created_at ?? now())->copy()->addMinutes((int) $minutes)->toIso8601String();
    }
}

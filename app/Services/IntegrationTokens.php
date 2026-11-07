<?php

namespace App\Services;

use App\Model\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Tokens für externe Integrationen (z. B. Home Assistant, Node-RED).
 *
 * Anders als App-Tokens (Fähigkeit `*`, globale Laufzeit aus `sanctum.expiration`) haben sie
 * eine eigene Laufzeit (auch unbegrenzt) und dürfen nur lesen – schreibend nur die ausdrücklich
 * gewählten Bereiche. Token-Verwaltung, Token-Refresh und Web-Anmeldelinks sind damit gesperrt.
 */
class IntegrationTokens
{
    public const ABILITY = 'integration';

    public const READ = 'integration:read';

    /** Optionale Schreibrechte: Schlüssel => Bezeichnung. */
    public const SCOPES = [
        'krankmeldung' => 'Krankmeldungen abgeben',
        'notifications' => 'Benachrichtigungen als gelesen markieren',
    ];

    /** Laufzeiten in Tagen; null = unbegrenzt. */
    public const LIFETIMES = [
        '90' => '90 Tage',
        '365' => '1 Jahr',
        'never' => 'Unbegrenzt',
    ];

    /** Schreibende Endpunkte je Bereich (Pfadmuster ohne führenden Slash). */
    private const WRITE_ROUTES = [
        'krankmeldung' => [
            'api/krankmeldung',
            'api/v1/parent/krankmeldungen',
        ],
        'notifications' => [
            'api/notification/read',
            'api/notification/readAll',
            'api/notification/readAllByType',
            'api/v1/notifications/read-all',
            'api/v1/notifications/*/read',
        ],
    ];

    public static function create(User $user, string $name, array $scopes, string $lifetime): NewAccessToken
    {
        $abilities = [self::ABILITY, self::READ];
        foreach (array_intersect(array_keys(self::SCOPES), $scopes) as $scope) {
            $abilities[] = self::ABILITY.':'.$scope;
        }

        $expiresAt = $lifetime === 'never' ? null : now()->addDays((int) $lifetime);

        return $user->createToken($name, $abilities, $expiresAt);
    }

    public static function isIntegration(mixed $token): bool
    {
        return $token instanceof PersonalAccessToken
            && in_array(self::ABILITY, $token->abilities ?? [], true);
    }

    /** Gewählte Schreibbereiche eines Integrations-Tokens. */
    public static function scopes(PersonalAccessToken $token): array
    {
        return array_values(array_filter(
            array_keys(self::SCOPES),
            fn (string $scope) => in_array(self::ABILITY.':'.$scope, $token->abilities ?? [], true)
        ));
    }

    /**
     * Darf das Integrations-Token diese Anfrage ausführen?
     * Lesen (GET/HEAD) immer, Schreiben nur in den freigegebenen Bereichen.
     */
    public static function allows(PersonalAccessToken $token, Request $request): bool
    {
        if ($request->isMethodSafe()) {
            return in_array(self::READ, $token->abilities ?? [], true);
        }

        foreach (self::scopes($token) as $scope) {
            if ($request->isMethod('POST') && $request->is(...self::WRITE_ROUTES[$scope])) {
                return true;
            }
        }

        return false;
    }
}

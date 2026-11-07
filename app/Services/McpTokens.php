<?php

namespace App\Services;

use App\Model\User;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Kurzlebige Tokens für den MCP-Server (z. B. für ein Chatbot-Snippet).
 *
 * Ein MCP-Token kann ausschließlich den MCP-Endpunkt aufrufen (siehe `RestrictMcpTokens`) und dort
 * nur lesen. Es läuft nach `mcp_server.token_lifetime` Minuten ab und berechtigt zu nichts anderem
 * der App-API – selbst wenn es beim Chatbot-Anbieter abhandenkommt, ist damit nichts zu ändern.
 */
class McpTokens
{
    public const ABILITY = 'mcp';

    public const READ = 'mcp:read';

    public const NAME = 'MCP (Chatbot)';

    /** Höchstzahl gleichzeitig gültiger MCP-Tokens je Nutzer (mehrere Tabs/Geräte). */
    private const MAX_ACTIVE = 5;

    public static function enabled(): bool
    {
        return (bool) config('mcp_server.enabled', false);
    }

    public static function create(User $user): NewAccessToken
    {
        $expiresAt = now()->addMinutes(self::lifetime());

        self::prune($user);

        return $user->createToken(self::NAME, [self::ABILITY, self::READ], $expiresAt);
    }

    public static function lifetime(): int
    {
        return max(5, min(24 * 60, (int) config('mcp_server.token_lifetime', 60)));
    }

    public static function isMcp(mixed $token): bool
    {
        return $token instanceof PersonalAccessToken
            && in_array(self::ABILITY, $token->abilities ?? [], true);
    }

    /**
     * Darf dieses Token den MCP-Server nutzen? App-Tokens (`*`) und MCP-Tokens ja,
     * Integrations-Tokens und andere eingeschränkte Tokens nicht.
     */
    public static function mayUseServer(mixed $token): bool
    {
        return $token !== null && method_exists($token, 'can') && $token->can(self::READ);
    }

    /** Abgelaufene MCP-Tokens löschen und die Zahl gültiger begrenzen. */
    private static function prune(User $user): void
    {
        $tokens = $user->tokens()->where('name', self::NAME)->orderByDesc('id')->get()
            ->filter(fn (PersonalAccessToken $t) => self::isMcp($t));

        $expired = $tokens->filter(fn (PersonalAccessToken $t) => $t->expires_at && $t->expires_at->isPast());
        $surplus = $tokens->diff($expired)->values()->slice(self::MAX_ACTIVE - 1);

        $ids = $expired->merge($surplus)->pluck('id');
        if ($ids->isNotEmpty()) {
            $user->tokens()->whereIn('id', $ids)->delete();
        }
    }
}

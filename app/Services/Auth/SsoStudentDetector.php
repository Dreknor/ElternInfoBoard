<?php

namespace App\Services\Auth;

use App\Settings\KeycloakSetting;

/**
 * Erkennt Schülerinnen und Schüler beim SSO-Login an ihrer Rolle
 * (docs/konzept-schuelerkonten.md §6.1, Paket S-02).
 *
 * Zwei Quellen:
 *  1. UCS@school: Claim `ucsschoolRole` (LDAP-Attribut, per Mapper im Token) mit
 *     Werten wie `student:school:ESZ`. Die Rolle `student` gilt immer als Schüler,
 *     ohne weitere Einstellung.
 *  2. Keycloak-Rollen: `realm_access.roles`, `resource_access.*.roles`, `roles`.
 *     Welche Rollen Schüler sind, steht in KeycloakSetting::$student_roles.
 * Gelesen wird aus den Benutzerdaten (userinfo), dem Access-Token und dem ID-Token.
 * E-Mail-Domain oder Gruppennamen werden bewusst nicht ausgewertet.
 *
 * Die Token stammen direkt vom Token-Endpunkt des IdP (TLS); hier werden nur
 * Claims gelesen, keine Signatur geprüft.
 */
class SsoStudentDetector
{
    public const MESSAGE = 'Für Schülerinnen und Schüler ist noch kein Zugang freigeschaltet.';

    /** UCS@school-Rolle für Schülerinnen und Schüler (`ucsschoolRole` = `student:school:<Schule>`). */
    public const UCS_STUDENT_ROLE = 'student';

    /** Mögliche Claim-Namen für das LDAP-Attribut `ucsschoolRole` (je nach Mapper). */
    private const UCS_ROLE_CLAIMS = ['ucsschoolRole', 'ucsschool_role', 'ucsschoolrole'];

    public function __construct(private readonly KeycloakSetting $settings) {}

    /**
     * Konfigurierte Schülerrollen (kleingeschrieben). Leer = Erkennung aus.
     *
     * @return list<string>
     */
    public function configuredRoles(): array
    {
        $raw = (string) ($this->settings->student_roles ?? '');

        return array_values(array_unique(array_filter(array_map(
            fn ($role) => mb_strtolower(trim($role)),
            explode(',', $raw)
        ))));
    }

    /**
     * Trägt der SSO-Nutzer eine der konfigurierten Schülerrollen?
     *
     * @param  object  $oidcUser  Socialite-User (Felder `user`, `token`, `accessTokenResponseBody`)
     */
    public function isStudent(object $oidcUser): bool
    {
        $claims = $this->claimSources($oidcUser);

        foreach ($claims as $source) {
            if (in_array(self::UCS_STUDENT_ROLE, $this->ucsSchoolRoles($source), true)) {
                return true;
            }
        }

        $configured = $this->configuredRoles();

        return $configured !== [] && array_intersect($configured, $this->roles($oidcUser)) !== [];
    }

    /**
     * Rollennamen aus `ucsschoolRole` (`student:school:ESZ` → `student`).
     *
     * @return list<string>
     */
    public function ucsSchoolRoles(array $claims): array
    {
        $roles = [];
        foreach (self::UCS_ROLE_CLAIMS as $name) {
            foreach ((array) ($claims[$name] ?? []) as $value) {
                if (is_string($value) && $value !== '') {
                    $roles[] = mb_strtolower(trim(explode(':', $value, 2)[0]));
                }
            }
        }

        return array_values(array_unique($roles));
    }

    /**
     * Alle Rollen aus Benutzerdaten und Token (kleingeschrieben).
     *
     * @return list<string>
     */
    public function roles(object $oidcUser): array
    {
        $roles = [];
        foreach ($this->claimSources($oidcUser) as $claims) {
            $roles = array_merge($roles, $this->rolesFromClaims($claims));
        }

        return array_values(array_unique(array_map(fn ($r) => mb_strtolower((string) $r), $roles)));
    }

    /**
     * Claims aus userinfo, Access-Token und ID-Token.
     *
     * @return list<array<string, mixed>>
     */
    private function claimSources(object $oidcUser): array
    {
        return [
            is_array($oidcUser->user ?? null) ? $oidcUser->user : [],
            $this->jwtClaims($oidcUser->token ?? null),
            $this->jwtClaims(data_get($oidcUser->accessTokenResponseBody ?? [], 'id_token')),
        ];
    }

    /**
     * @return list<mixed>
     */
    private function rolesFromClaims(array $claims): array
    {
        $roles = (array) data_get($claims, 'realm_access.roles', []);

        foreach ((array) data_get($claims, 'resource_access', []) as $client) {
            $roles = array_merge($roles, (array) data_get($client, 'roles', []));
        }

        $top = $claims['roles'] ?? [];
        $roles = array_merge($roles, is_array($top) ? $top : [$top]);

        return array_filter($roles, fn ($r) => is_string($r) && $r !== '');
    }

    private function jwtClaims(mixed $jwt): array
    {
        if (! is_string($jwt) || substr_count($jwt, '.') !== 2) {
            return [];
        }

        $payload = explode('.', $jwt)[1];
        $json = base64_decode(strtr($payload, '-_', '+/').str_repeat('=', (4 - strlen($payload) % 4) % 4), true);
        $claims = $json === false ? null : json_decode($json, true);

        return is_array($claims) ? $claims : [];
    }
}

<?php

namespace App\Services\Ucs\Dto;

/**
 * Typisiertes DTO für einen Kelvin-Elternteil-Account (role=legal_guardian).
 *
 * Normalisierungen in fromArray():
 * – username:   aus 'name' (Kelvin), 'username' oder URL-Basename extrahiert
 * – recordUid:  null wenn Kelvin kein record_uid liefert (nie Leerstring)
 * – email:      mailPrimaryAddress („email") oder UDM-Kontaktadresse („e-mail")
 * – roles:      URL-Strings werden auf Short-Names reduziert
 * – school(s):  URL-Strings werden auf Short-Names reduziert
 *
 * Hinweis: record_uid ist die Kennung aus dem Quellsystem (z. B. Schulverwaltung)
 * und NICHT identisch mit dem OIDC-sub-Claim des UCS-Keycloak.
 *
 * @see https://docs.software-univention.de/ucsschool-kelvin-rest-api/resource-users.html
 */
readonly class KelvinUserDto
{
    public function __construct(
        public string  $username,
        public ?string $recordUid,
        public string  $firstname,
        public string  $lastname,
        public ?string $email,
        public string  $school,
        /** @var list<string> */
        public array   $roles,
        /**
         * Liste der legal_wards (Kelvin liefert immer URLs).
         *
         * @var list<string>
         */
        public array   $legalWards,
        public ?string $url,
        /** Originale API-Daten für Debugging / Auditing */
        public array   $raw,
        /** Account in UCS deaktiviert */
        public bool    $disabled = false,
        /** @var list<string> Alle Schulen des Accounts (Short-Names) */
        public array   $schools = [],
    ) {}

    /**
     * Factory aus einem Kelvin-API-Response-Array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $school  = KelvinNormalizer::extractName((string) ($data['school'] ?? ''));
        $schools = KelvinNormalizer::normalizeRoles((array) ($data['schools'] ?? []));

        if ($school !== '' && ! in_array($school, $schools, true)) {
            $schools[] = $school;
        }

        return new self(
            username:   KelvinNormalizer::resolveUsername($data),
            recordUid:  KelvinNormalizer::resolveRecordUid($data),
            firstname:  (string) ($data['firstname'] ?? ''),
            lastname:   (string) ($data['lastname']  ?? ''),
            email:      KelvinNormalizer::resolveEmail($data),
            school:     $school,
            roles:      KelvinNormalizer::normalizeRoles((array) ($data['roles'] ?? [])),
            legalWards: array_values((array) ($data['legal_wards'] ?? [])),
            url:        $data['url'] ?? null,
            raw:        $data,
            disabled:   (bool) ($data['disabled'] ?? false),
            schools:    $schools,
        );
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    /** Gehört der Account (case-insensitiv) zur angegebenen Schule? */
    public function belongsToSchool(string $school): bool
    {
        foreach ($this->schools as $s) {
            if (KelvinNormalizer::sameSchool($s, $school)) {
                return true;
            }
        }

        return false;
    }
}

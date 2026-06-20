<?php

namespace App\Services\Ucs\Dto;

/**
 * Typisiertes DTO für einen Kelvin-Elternteil-Account (role=legal_guardian).
 *
 * Normalisierungen in fromArray():
 * – username:   aus 'username', 'name' oder URL-Basename extrahiert
 * – recordUid:  null wenn Kelvin kein record_uid liefert (nie Leerstring)
 * – roles:      URL-Strings werden auf Short-Names reduziert
 * – school:     URL-Strings werden auf Short-Names reduziert
 *
 * @see docs/kelvin-api-endpunkte.md#3-eltern-auflisten-get-users--legal_guardian
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
         * Liste der legal_wards (als URL-Strings oder Usernames).
         *
         * @var list<string>
         */
        public array   $legalWards,
        public ?string $url,
        /** Originale API-Daten für Debugging / Auditing */
        public array   $raw,
    ) {}

    /**
     * Factory aus einem Kelvin-API-Response-Array.
     *
     * Behandelt sowohl Kelvin-Installationen, die Short-Names liefern,
     * als auch solche, die vollständige URLs für Rollen und Schule liefern.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            username:   KelvinNormalizer::resolveUsername($data),
            recordUid:  KelvinNormalizer::resolveRecordUid($data),
            firstname:  (string) ($data['firstname'] ?? ''),
            lastname:   (string) ($data['lastname']  ?? ''),
            email:      ($data['email'] ?? null) ?: null,
            school:     KelvinNormalizer::extractName((string) ($data['school'] ?? '')),
            roles:      KelvinNormalizer::normalizeRoles((array) ($data['roles'] ?? [])),
            legalWards: (array) ($data['legal_wards'] ?? []),
            url:        $data['url'] ?? null,
            raw:        $data,
        );
    }
}

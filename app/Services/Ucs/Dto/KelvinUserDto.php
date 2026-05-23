<?php

namespace App\Services\Ucs\Dto;

/**
 * Typisiertes DTO für einen Kelvin-Elternteil-Account (role=legal_guardian).
 *
 * @see docs/kelvin-api-endpunkte.md#3-eltern-auflisten-get-users--legal_guardian
 */
readonly class KelvinUserDto
{
    public function __construct(
        public string  $username,
        public string  $recordUid,
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
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            username:   $data['username']    ?? '',
            recordUid:  $data['record_uid']  ?? '',
            firstname:  $data['firstname']   ?? '',
            lastname:   $data['lastname']    ?? '',
            email:      $data['email']       ?? null,
            school:     $data['school']      ?? '',
            roles:      (array) ($data['roles'] ?? []),
            legalWards: (array) ($data['legal_wards'] ?? []),
            url:        $data['url']         ?? null,
            raw:        $data,
        );
    }
}


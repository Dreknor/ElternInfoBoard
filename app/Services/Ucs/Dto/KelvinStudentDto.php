<?php

namespace App\Services\Ucs\Dto;

/**
 * Typisiertes DTO für einen Kelvin-Schüler-Account (role=student).
 *
 * @see docs/kelvin-api-endpunkte.md#4-schüler-auflisten-get-users--student
 */
readonly class KelvinStudentDto
{
    public function __construct(
        public string  $username,
        public string  $recordUid,
        public string  $firstname,
        public string  $lastname,
        public string  $school,
        /** @var list<string> */
        public array   $roles,
        /**
         * Klassen-Zuordnung, z. B. {"example": ["3a"]}.
         *
         * @var array<string, list<string>>
         */
        public array   $schoolClasses,
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
            username:     $data['username']      ?? '',
            recordUid:    $data['record_uid']    ?? '',
            firstname:    $data['firstname']     ?? '',
            lastname:     $data['lastname']      ?? '',
            school:       $data['school']        ?? '',
            roles:        (array) ($data['roles'] ?? []),
            schoolClasses:(array) ($data['school_classes'] ?? []),
            url:          $data['url']           ?? null,
            raw:          $data,
        );
    }

    /**
     * Gibt den ersten Klassennamen für die angegebene Schule zurück.
     * Gibt null zurück, wenn keine Klasse vorhanden (Warnung im Sync-Log).
     */
    public function primaryClass(string $school): ?string
    {
        return $this->schoolClasses[$school][0] ?? null;
    }
}


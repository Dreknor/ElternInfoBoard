<?php

namespace App\Services\Ucs\Dto;

/**
 * Typisiertes DTO für einen Kelvin-Schüler-Account (role=student).
 *
 * Normalisierungen in fromArray():
 * – username:   aus 'name' (Kelvin), 'username' oder URL-Basename extrahiert
 * – recordUid:  null wenn Kelvin kein record_uid liefert (nie Leerstring)
 * – roles:      URL-Strings werden auf Short-Names reduziert
 * – school(s):  URL-Strings werden auf Short-Names reduziert
 *
 * @see https://docs.software-univention.de/ucsschool-kelvin-rest-api/resource-users.html
 */
readonly class KelvinStudentDto
{
    public function __construct(
        public string  $username,
        public ?string $recordUid,
        public string  $firstname,
        public string  $lastname,
        public string  $school,
        /** @var list<string> */
        public array   $roles,
        /**
         * Klassen-Zuordnung, z. B. {"DEMOSCHOOL": ["3a"]}.
         *
         * @var array<string, list<string>>
         */
        public array   $schoolClasses,
        public ?string $url,
        /** Originale API-Daten für Debugging / Auditing */
        public array   $raw,
        /**
         * Erziehungsberechtigte als URLs/Usernames (Gegenstück zu legal_wards).
         *
         * @var list<string>
         */
        public array   $legalGuardians = [],
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
            username:       KelvinNormalizer::resolveUsername($data),
            recordUid:      KelvinNormalizer::resolveRecordUid($data),
            firstname:      (string) ($data['firstname'] ?? ''),
            lastname:       (string) ($data['lastname']  ?? ''),
            school:         $school,
            roles:          KelvinNormalizer::normalizeRoles((array) ($data['roles'] ?? [])),
            schoolClasses:  (array) ($data['school_classes'] ?? []),
            url:            $data['url'] ?? null,
            raw:            $data,
            legalGuardians: array_values((array) ($data['legal_guardians'] ?? [])),
            schools:        $schools,
        );
    }

    /**
     * Klassennamen für die angegebene Schule.
     *
     * Der Schlüssel in school_classes ist der kanonische OU-Name (z. B. „EVSR"),
     * UcsSetting::school kann aber abweichend geschrieben sein („evsr").
     * Daher case-insensitiver Lookup.
     *
     * @return list<string>
     */
    public function classesFor(string $school): array
    {
        if (isset($this->schoolClasses[$school])) {
            return array_values((array) $this->schoolClasses[$school]);
        }

        foreach ($this->schoolClasses as $key => $classes) {
            if (KelvinNormalizer::sameSchool((string) $key, $school)) {
                return array_values((array) $classes);
            }
        }

        return [];
    }

    /**
     * Gibt den ersten Klassennamen für die angegebene Schule zurück.
     * Gibt null zurück, wenn keine Klasse vorhanden (Warnung im Sync-Log).
     */
    public function primaryClass(string $school): ?string
    {
        return $this->classesFor($school)[0] ?? null;
    }

    /** Gehört der Account (case-insensitiv) zur angegebenen Schule? */
    public function belongsToSchool(string $school): bool
    {
        foreach ($this->schools as $s) {
            if (KelvinNormalizer::sameSchool($s, $school)) {
                return true;
            }
        }

        return $this->classesFor($school) !== [];
    }
}

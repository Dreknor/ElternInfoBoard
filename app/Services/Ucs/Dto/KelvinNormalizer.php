<?php

namespace App\Services\Ucs\Dto;

/**
 * Statische Hilfsmethoden zur Normalisierung von Kelvin-API-Feldern.
 *
 * Der Kelvin-REST-API liefert je nach Version und Konfiguration Felder
 * als Short-Name oder als vollständige URL:
 *
 *   roles:  "legal_guardian"
 *           "https://…/ucsschool/kelvin/v1/roles/legal_guardian"
 *
 *   school: "EVSR"
 *           "https://…/ucsschool/kelvin/v1/schools/EVSR"
 *
 *   username: "evsr-mamu99"   oder leer – dann steht der Name in:
 *              • $data['name']
 *              • basename($data['url'])
 *
 * Diese Klasse wird ausschließlich in KelvinUserDto und KelvinStudentDto
 * (und ggf. weiteren DTOs) verwendet.
 */
final class KelvinNormalizer
{
    private function __construct() {}

    /**
     * Extrahiert den kurzen Bezeichner aus einem URL- oder Short-Name-String.
     *
     * „https://example.com/ucsschool/kelvin/v1/roles/legal_guardian" → „legal_guardian"
     * „https://example.com/ucsschool/kelvin/v1/schools/EVSR"        → „EVSR"
     * „legal_guardian"                                               → „legal_guardian"
     * „EVSR"                                                         → „EVSR"
     */
    public static function extractName(string $value): string
    {
        if (str_starts_with($value, 'http')) {
            return rawurldecode(
                basename(parse_url($value, PHP_URL_PATH) ?? $value)
            );
        }

        return $value;
    }

    /**
     * Normalisiert ein Rollen-Array: URLs werden auf Short-Names reduziert,
     * Duplikate und Leerstrings werden entfernt.
     *
     * @param  mixed[]  $roles
     * @return list<string>
     */
    public static function normalizeRoles(array $roles): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn ($r) => self::extractName((string) $r), $roles)
        )));
    }

    /**
     * Löst den Benutzernamen aus einem Kelvin-API-Array auf.
     *
     * Priorität:
     *  1. $data['username']  (Standard-Feld)
     *  2. $data['name']      (einige Kelvin-Versionen liefern nur dieses Feld)
     *  3. basename($data['url'])  (letzter Fallback: aus der Ressource-URL)
     *
     * @param  array<string, mixed>  $data
     */
    public static function resolveUsername(array $data): string
    {
        $username = trim((string) ($data['username'] ?? $data['name'] ?? ''));

        if ($username === '' && ! empty($data['url'])) {
            $username = rawurldecode(
                basename(parse_url((string) $data['url'], PHP_URL_PATH) ?? '')
            );
        }

        return $username;
    }

    /**
     * Gibt die record_uid als Nullable-String zurück.
     *
     * Kelvin liefert record_uid in manchen Installationen als null.
     * Wir speichern ihn nie als Leerstring – entweder als validen UUID-String
     * oder als null, damit DB-Constraints und Matching korrekt funktionieren.
     *
     * @param  array<string, mixed>  $data
     */
    public static function resolveRecordUid(array $data): ?string
    {
        $uid = $data['record_uid'] ?? null;

        if ($uid === null || $uid === '') {
            return null;
        }

        return (string) $uid;
    }
}


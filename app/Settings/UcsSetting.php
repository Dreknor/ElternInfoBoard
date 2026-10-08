<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class UcsSetting extends Settings
{
    // --- Aktivierung ---
    /** Master-Schalter: UCS-Integration aktiv/inaktiv */
    public bool $enabled;

    // --- Kelvin REST API ---
    /** https://<ucs-host>/ucsschool/kelvin/v1 */
    public ?string $kelvin_base_url;

    /** Service-Account-Username für die Kelvin API */
    public ?string $kelvin_username;

    /** Service-Account-Passwort – verschlüsselt in der Datenbank */
    public ?string $kelvin_password;

    /** Pagination-Seitengröße für Kelvin API (empfohlen: 200) */
    public int $kelvin_page_size;

    /** HTTP-Timeout in Sekunden für Kelvin API Requests */
    public int $kelvin_timeout;

    /** Token-TTL in Sekunden (Standard: 3300 = 55 min) */
    public int $kelvin_token_ttl;

    // --- Schule (Single-School) ---
    /** Name der konfigurierten Schule, z. B. "GS-XY" */
    public ?string $school;

    // --- Sync-Verhalten ---
    /** Nächtlicher Sync an/aus */
    public bool $sync_enabled;

    /** Cron-Ausdruck für den Scheduler, z. B. "30 2 * * *" */
    public string $sync_cron;

    /** JIT-Sync beim OIDC-Login aktivieren */
    public bool $on_login_fallback;

    /** Timeout für JIT-Sync beim Login in Sekunden */
    public int $on_login_timeout;

    /** Tage bis zum Hard-Delete verwaister Sync-Objekte */
    public int $purge_after_days;

    // --- Letzte Sync-Telemetrie (read-only Anzeige) ---
    public ?string $last_sync_at;

    /** success | failed | running */
    public ?string $last_sync_status;

    public ?string $last_sync_message;

    public ?int $last_sync_parents;

    public ?int $last_sync_students;

    public static function group(): string
    {
        return 'ucs';
    }

    /**
     * Felder, die verschlüsselt in der Datenbank gespeichert werden.
     * Spatie Laravel Settings ver- und entschlüsselt automatisch.
     */
    public static function encrypted(): array
    {
        return ['kelvin_password'];
    }
}


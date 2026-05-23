<?php

/**
 * UCS@school / Kelvin Integration – Bootstrap-Defaults
 *
 * WICHTIG: Dieser Config-File enthält ausschließlich nicht-konfigurierende
 * Defaults für die Settings-Migration (§14.2) und den Logging-Channel-Namen.
 * Zur Laufzeit lesen KelvinClient und UcsSyncService AUSSCHLIESSLICH aus
 * App\Settings\UcsSetting (§14.3), NICHT aus config('ucs.*').
 *
 * @see App\Settings\UcsSetting
 * @see database/settings/2026_05_22_000000_create_ucs_settings.php
 */
return [

    'kelvin' => [
        // Defaults für Settings-Migration / Tests; zur Laufzeit nicht direkt benutzt
        'token_ttl_default' => 55 * 60, // 3300 s
        'timeout_default'   => 30,      // s
        'page_size_default' => 200,
    ],

    'sync' => [
        'on_login_timeout_default' => 5,  // s, harter Cap für JIT-Sync
        'purge_after_days_default' => 14, // Karenz bis Hard-Delete verwaister Klassen
    ],

    'logging' => [
        'channel' => 'ucs', // wird in config/logging.php definiert
    ],

];


<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // --- Aktivierung ---
        $this->migrator->add('ucs.enabled', env('UCS_ENABLED', false));

        // --- Kelvin REST API ---
        $this->migrator->add('ucs.kelvin_base_url', env('UCS_KELVIN_BASE_URL'));
        $this->migrator->add('ucs.kelvin_username', env('UCS_KELVIN_USER'));
        $this->migrator->addEncrypted('ucs.kelvin_password', env('UCS_KELVIN_PASSWORD'));
        $this->migrator->add('ucs.kelvin_page_size', (int) env('UCS_KELVIN_PAGE_SIZE', 200));
        $this->migrator->add('ucs.kelvin_timeout', (int) env('UCS_KELVIN_TIMEOUT', 30));
        $this->migrator->add('ucs.kelvin_token_ttl', (int) env('UCS_KELVIN_TOKEN_TTL', 3300));

        // --- Schule (Single-School) ---
        $this->migrator->add('ucs.school', env('UCS_SCHOOL'));

        // --- Sync-Verhalten ---
        $this->migrator->add('ucs.sync_enabled', (bool) env('UCS_SYNC_ENABLED', true));
        $this->migrator->add('ucs.sync_cron', env('UCS_SYNC_CRON', '30 2 * * *'));
        $this->migrator->add('ucs.on_login_fallback', (bool) env('UCS_SYNC_ON_LOGIN', true));
        $this->migrator->add('ucs.on_login_timeout', (int) env('UCS_SYNC_ON_LOGIN_TIMEOUT', 5));
        $this->migrator->add('ucs.purge_after_days', (int) env('UCS_PURGE_AFTER_DAYS', 14));

        // --- Letzte Sync-Telemetrie (initial null) ---
        $this->migrator->add('ucs.last_sync_at', null);
        $this->migrator->add('ucs.last_sync_status', null);
        $this->migrator->add('ucs.last_sync_message', null);
        $this->migrator->add('ucs.last_sync_parents', null);
        $this->migrator->add('ucs.last_sync_students', null);
    }
};


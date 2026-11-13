<?php

namespace App\Console\Commands;

use App\Model\SecurityEvent;
use App\Services\Elternabfragen\ResponseService;
use Illuminate\Console\Command;

class CleanupSecurityLog extends Command
{
    protected $signature = 'security-log:cleanup';

    protected $description = 'Löscht Einträge des Sicherheitsprotokolls nach Ablauf der Aufbewahrungsfrist und kürzt IP-Adresse/User-Agent älterer Einträge.';

    public function handle(): int
    {
        $retention = max(1, (int) config('security_log.retention_days'));
        $anonymizeAfter = max(0, (int) config('security_log.anonymize_ip_after_days'));

        $deleted = SecurityEvent::where('created_at', '<', now()->subDays($retention))->delete();

        // Idempotent: nur Einträge mit User-Agent oder noch ungekürzter IP werden verändert
        $anonymized = 0;
        SecurityEvent::where('created_at', '<', now()->subDays($anonymizeAfter))
            ->where(fn ($q) => $q->whereNotNull('user_agent')->orWhereNotNull('ip_address'))
            ->chunkById(500, function ($events) use (&$anonymized) {
                foreach ($events as $event) {
                    $ip = ResponseService::anonymizeIp($event->ip_address);
                    if ($ip === $event->ip_address && $event->user_agent === null) {
                        continue;
                    }
                    $event->forceFill(['ip_address' => $ip, 'user_agent' => null])->save();
                    $anonymized++;
                }
            });

        $this->info("✓ {$deleted} security_events gelöscht, {$anonymized} anonymisiert.");

        return self::SUCCESS;
    }
}

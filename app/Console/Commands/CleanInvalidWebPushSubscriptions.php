<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanInvalidWebPushSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webpush:clean-invalid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Entfernt ungültige WebPush-Subscriptions mit ungerader Hex-String-Länge';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Suche nach ungültigen WebPush-Subscriptions...');

        $subscriptions = DB::table('push_subscriptions')->get();
        $invalidCount = 0;
        $invalidIds = [];

        foreach ($subscriptions as $subscription) {
            // Prüfe ob die Schlüssel eine gerade Länge haben
            $p256dh = $subscription->p256dh ?? '';
            $auth = $subscription->auth ?? '';

            if ((strlen($p256dh) % 2 !== 0) || (strlen($auth) % 2 !== 0)) {
                $invalidIds[] = $subscription->id;
                $invalidCount++;
                $this->warn("Ungültige Subscription gefunden: ID {$subscription->id}, User ID: {$subscription->subscribable_id}");
            }
        }

        if ($invalidCount > 0) {
            if ($this->confirm("Möchten Sie {$invalidCount} ungültige Subscription(s) löschen?", true)) {
                DB::table('push_subscriptions')
                    ->whereIn('id', $invalidIds)
                    ->delete();

                $this->info("✓ {$invalidCount} ungültige Subscription(s) wurden gelöscht.");
            } else {
                $this->info('Vorgang abgebrochen.');
            }
        } else {
            $this->info('✓ Keine ungültigen Subscriptions gefunden.');
        }

        return Command::SUCCESS;
    }
}


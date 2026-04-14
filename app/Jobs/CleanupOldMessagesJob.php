<?php

namespace App\Jobs;

use App\Model\Conversation;
use App\Settings\MessengerSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanupOldMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(MessengerSetting $settings): void
    {
        $defaultDays = $settings->auto_delete_days;

        Conversation::where('is_active', true)->each(function (Conversation $conv) use ($defaultDays) {
            // Fallback auf globale Einstellung wenn Konversation keinen eigenen Wert hat
            $days = $conv->auto_delete_days ?? $defaultDays;

            if (! $days || $days <= 0) {
                return; // 0 oder null = kein automatisches Löschen
            }

            $cutoff = now()->subDays($days);

            $conv->messages()
                ->whereNull('deleted_at')
                ->where('created_at', '<', $cutoff)
                ->each(fn ($msg) => $msg->delete()); // Soft-Delete pro Nachricht (löst Observer aus)
        });
    }
}


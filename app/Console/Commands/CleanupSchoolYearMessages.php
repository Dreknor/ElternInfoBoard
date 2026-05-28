<?php

namespace App\Console\Commands;

use App\Model\Conversation;
use App\Model\Message;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupSchoolYearMessages extends Command
{
    protected $signature = 'messenger:cleanup-school-year
                            {--cutoff= : Manueller Stichtag (YYYY-MM-DD), Default = letzter 1. August}
                            {--dry-run : Nur zählen, nicht löschen}';

    protected $description = 'Löscht Messenger-Nachrichten des vergangenen Schuljahres (vor dem letzten 1. August).';

    public function handle(): int
    {
        $cutoff = $this->option('cutoff')
            ? Carbon::parse($this->option('cutoff'))->startOfDay()
            : $this->lastAugustFirst();

        $count = Message::withTrashed()->where('created_at', '<', $cutoff)->count();

        $this->info("Messenger-Nachrichten vor {$cutoff->format('d.m.Y')}: {$count}");

        if ($this->option('dry-run')) {
            $this->warn('Dry-Run: keine Änderungen.');

            return self::SUCCESS;
        }

        if ($count === 0) {
            return self::SUCCESS;
        }

        // Anhänge & abhängige Reports werden via FK-Cascade / Observer entfernt.
        $deleted = 0;
        Message::withTrashed()
            ->where('created_at', '<', $cutoff)
            ->chunkById(200, function ($msgs) use (&$deleted) {
                foreach ($msgs as $m) {
                    $m->forceDelete();
                    $deleted++;
                }
            });

        // Direktkonversationen ohne Nachrichten deaktivieren
        $deactivated = DB::table('conversations')
            ->where('type', 'direct')
            ->whereNotIn('id', function ($q) {
                $q->select('conversation_id')->from('messages');
            })
            ->update(['is_active' => false, 'updated_at' => now()]);

        Log::info('Messenger Schuljahresend-Cleanup', [
            'deleted_messages'        => $deleted,
            'deactivated_directs'     => $deactivated,
            'cutoff'                  => $cutoff->toDateString(),
        ]);

        $this->info("✓ {$deleted} Nachrichten gelöscht, {$deactivated} leere Direktkonversationen deaktiviert.");

        return self::SUCCESS;
    }

    private function lastAugustFirst(): Carbon
    {
        return now()->month < 8
            ? now()->subYear()->month(8)->day(1)->startOfDay()
            : now()->month(8)->day(1)->startOfDay();
    }
}


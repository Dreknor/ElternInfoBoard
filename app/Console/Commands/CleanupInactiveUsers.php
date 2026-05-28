<?php

namespace App\Console\Commands;

use App\Model\Notification;
use App\Model\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class CleanupInactiveUsers extends Command
{
    protected $signature = 'users:cleanup
                            {--purge-days=90 : Soft-deleted User nach X Tagen endgültig löschen}
                            {--report : Vorschlagsliste inaktiver User an Administratoren senden, ohne zu löschen}
                            {--dry-run : Nur zählen, nicht löschen}';

    protected $description = 'User-Cleanup: Force-Delete für lange soft-gelöschte User; optionaler Vorschlagsbericht.';

    /**
     * Geschützte Rollen werden niemals automatisch entfernt.
     */
    private const PROTECTED_ROLES = ['Mitarbeiter', 'Schulbegleiter', 'Administrator', 'Vereinsmitglieder'];

    public function handle(): int
    {
        if ($this->option('report')) {
            return $this->sendInactivityReport();
        }

        return $this->purgeSoftDeleted();
    }

    private function purgeSoftDeleted(): int
    {
        $purgeDays = (int) $this->option('purge-days');
        $cutoff    = now()->subDays($purgeDays);

        $query = User::onlyTrashed()->where('deleted_at', '<', $cutoff);
        $count = $query->count();

        $this->info("Soft-deleted User älter als {$purgeDays} Tage: {$count}");

        if ($this->option('dry-run')) {
            $this->warn('Dry-Run: keine Änderungen.');

            return self::SUCCESS;
        }

        if ($count === 0) {
            return self::SUCCESS;
        }

        $deleted = 0;
        $errors  = 0;

        $query->chunkById(50, function ($users) use (&$deleted, &$errors) {
            foreach ($users as $user) {
                try {
                    // Schutz: keine geschützte Rolle
                    if ($user->roles()->whereIn('name', self::PROTECTED_ROLES)->exists()) {
                        continue;
                    }
                    $user->forceDelete();
                    $deleted++;
                } catch (\Throwable $e) {
                    $errors++;
                    Log::error('User-Cleanup Force-Delete Fehler', [
                        'user_id' => $user->id,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }
        });

        Log::info('User-Cleanup Force-Delete abgeschlossen', [
            'deleted' => $deleted,
            'errors'  => $errors,
            'cutoff'  => $cutoff->toDateTimeString(),
        ]);

        $this->info("✓ {$deleted} User endgültig gelöscht (Fehler: {$errors}).");

        return self::SUCCESS;
    }

    private function sendInactivityReport(): int
    {
        $candidates = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', self::PROTECTED_ROLES))
            ->whereDoesntHave('groups')
            ->where('created_at', '<', now()->subMonths(6))
            ->where(function ($q) {
                $q->whereNull('last_online_at')
                  ->orWhere('last_online_at', '<', now()->subYear());
            })
            ->get();

        $this->info("Vorschlagsliste: {$candidates->count()} potenziell inaktive User.");

        if ($this->option('dry-run') || $candidates->isEmpty()) {
            foreach ($candidates as $u) {
                $this->line(" - #{$u->id} {$u->name} <{$u->email}> – last_online: ".($u->last_online_at?->format('d.m.Y') ?? 'nie'));
            }

            return self::SUCCESS;
        }

        // In-App-Benachrichtigung an alle Administratoren
        $admins = optional(Role::query()->where('name', 'Administrator')->first())->users()->get() ?? collect();

        $list = $candidates->take(50)->map(fn ($u) => "#{$u->id} {$u->name}")->implode(', ');
        $more = $candidates->count() > 50 ? ' (+'.($candidates->count() - 50).' weitere)' : '';

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title'   => 'Inaktive User – Cleanup-Vorschlag',
                'message' => "{$candidates->count()} Konten ohne Gruppe/Rolle, kein Login seit > 1 Jahr: {$list}{$more}",
                'type'    => 'info',
            ]);
        }

        Log::info('User-Cleanup Inaktivitätsbericht versandt', [
            'candidates' => $candidates->count(),
            'admins'     => $admins->count(),
        ]);

        return self::SUCCESS;
    }
}


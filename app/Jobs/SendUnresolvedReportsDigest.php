<?php

namespace App\Jobs;

use App\Model\MessageReport;
use App\Model\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Throwable;

class SendUnresolvedReportsDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function handle(): void
    {
        $unresolvedCount = MessageReport::whereNull('resolved_at')->count();

        if ($unresolvedCount === 0) {
            return;
        }

        // Alle Moderatoren benachrichtigen
        $permission  = Permission::findByName('moderate messages', 'web');
        $moderators  = $permission ? $permission->users : collect();

        $reports = MessageReport::with(['message.sender', 'reporter'])
            ->whereNull('resolved_at')
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        $moderators->each(function (User $moderator) use ($reports, $unresolvedCount) {
            try {
                Mail::send(
                    [],
                    [],
                    function (Message $mail) use ($moderator, $reports, $unresolvedCount) {
                        $reportLines = $reports->map(fn ($r) =>
                            "- Gemeldet von {$r->reporter?->name}: \"{$r->reason}\" (Nachricht von {$r->message?->sender?->name})"
                        )->join("\n");

                        $mail->to($moderator->email, $moderator->name)
                            ->subject("[ElternInfoBoard] {$unresolvedCount} ungelöste Nachrichtenmeldungen")
                            ->text(
                                "Hallo {$moderator->name},\n\n" .
                                "es gibt aktuell {$unresolvedCount} ungelöste Nachrichtenmeldung(en):\n\n" .
                                $reportLines . "\n\n" .
                                "Bitte prüfe und bearbeite diese unter: " . url('messenger/admin/reports') . "\n\n" .
                                "Viele Grüße\n" . config('app.name')
                            );
                    }
                );
            } catch (Throwable $e) {
                // Fehler bei einem Empfänger darf den Digest für die übrigen nicht abbrechen
                Log::error('SendUnresolvedReportsDigest: Versand an Moderator fehlgeschlagen', [
                    'moderator_id' => $moderator->id,
                    'message' => $e->getMessage(),
                ]);
            }
        });
    }
}


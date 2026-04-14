<?php

namespace App\Jobs;

use App\Mail\ReminderEscalationMail;
use App\Mail\ReminderMail;
use App\Model\ChildCheckIn;
use App\Model\Notification;
use App\Model\Post;
use App\Model\ReadReceipts;
use App\Model\ReminderLog;
use App\Model\Rueckmeldungen;
use App\Model\User;
use App\Model\UserRueckmeldungen;
use App\Notifications\ReminderPushNotification;
use App\Settings\ReminderSetting;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ReminderSetting $settings): void
    {
        $now = now();

        Log::info('[ProcessRemindersJob] Starte Erinnerungsverarbeitung', [
            'time' => $now->toDateTimeString(),
        ]);

        $stats = ['rueckmeldungen' => 0, 'read_receipts' => 0, 'attendance' => 0];

        // ── TEIL A: Rückmeldungen (Pflicht) ──────────────────────
        if ($settings->include_rueckmeldungen) {
            $stats['rueckmeldungen'] = $this->processRueckmeldungen($settings, $now);
        }

        // ── TEIL B: Lesebestätigungen ─────────────────────────────
        if ($settings->include_read_receipts) {
            $stats['read_receipts'] = $this->processReadReceipts($settings, $now);
        }

        // ── TEIL C: Anwesenheitsabfragen ──────────────────────────
        if ($settings->include_attendance_queries) {
            $stats['attendance'] = $this->processAttendanceQueries($settings, $now);
        }

        Log::info('[ProcessRemindersJob] Erinnerungsverarbeitung abgeschlossen', $stats);
    }

    // ═══════════════════════════════════════════════════════════════
    //  TEIL A: Pflicht-Rückmeldungen
    // ═══════════════════════════════════════════════════════════════

    private function processRueckmeldungen(ReminderSetting $settings, Carbon $now): int
    {
        $count = 0;

        $rueckmeldungen = Rueckmeldungen::where('pflicht', true)
            ->whereNotNull('ende')
            ->whereHas('post', fn ($q) => $q->where('released', 1))
            ->with(['post.users'])
            ->get();

        foreach ($rueckmeldungen as $rueckmeldung) {
            $deadline = $rueckmeldung->ende;
            $post = $rueckmeldung->post;

            if (!$post) {
                continue;
            }

            $allUsers = $post->users->unique('id');

            foreach ($allUsers as $user) {
                // Prüfe ob User bereits geantwortet hat
                $hasResponded = UserRueckmeldungen::where('post_id', $post->id)
                    ->where('users_id', $user->id)
                    ->exists();

                if ($hasResponded) {
                    continue;
                }

                // Bestimme die passende Erinnerungsstufe
                $level = $this->determineLevel($settings, $deadline, $now);

                if ($level === null) {
                    continue;
                }

                // Prüfe ob diese Stufe bereits gesendet wurde
                $alreadySent = ReminderLog::where('remindable_type', Rueckmeldungen::class)
                    ->where('remindable_id', $rueckmeldung->id)
                    ->where('user_id', $user->id)
                    ->where('level', $level)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                // Erinnerung versenden
                $this->sendReminder($user, $post, $rueckmeldung, $level, $settings, $deadline, 'rueckmeldung');
                $count++;
            }
        }

        return $count;
    }

    // ═══════════════════════════════════════════════════════════════
    //  TEIL B: Lesebestätigungen
    // ═══════════════════════════════════════════════════════════════

    private function processReadReceipts(ReminderSetting $settings, Carbon $now): int
    {
        $count = 0;

        $posts = Post::where('read_receipt', true)
            ->where('released', 1)
            ->with(['users', 'receipts'])
            ->get();

        foreach ($posts as $post) {
            $deadline = $post->read_receipt_deadline ?? $post->archiv_ab;
            if (!$deadline) {
                continue;
            }

            $allUsers = $post->users->unique('id');
            $confirmedUserIds = $post->receipts
                ->whereNotNull('confirmed_at')
                ->pluck('user_id')
                ->toArray();

            foreach ($allUsers as $user) {
                if (in_array($user->id, $confirmedUserIds)) {
                    continue;
                }

                $level = $this->determineLevel($settings, $deadline, $now);
                if ($level === null) {
                    continue;
                }

                $alreadySent = ReminderLog::where('remindable_type', Post::class)
                    ->where('remindable_id', $post->id)
                    ->where('user_id', $user->id)
                    ->where('level', $level)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $this->sendReminder($user, $post, $post, $level, $settings, $deadline, 'lesebestaetigung');
                $count++;
            }
        }

        return $count;
    }

    // ═══════════════════════════════════════════════════════════════
    //  TEIL C: Anwesenheitsabfragen
    // ═══════════════════════════════════════════════════════════════

    private function processAttendanceQueries(ReminderSetting $settings, Carbon $now): int
    {
        $count = 0;

        // Alle unbeantworteten Anwesenheitsabfragen innerhalb des Erinnerungsfensters
        $windowEnd = $now->copy()->addDays($settings->level1_days_before_deadline)->toDateString();
        $openCheckIns = ChildCheckIn::query()
            ->whereNull('should_be')
            ->whereNotNull('lock_at')
            ->where('lock_at', '>=', $now->toDateString())
            ->where('lock_at', '<=', $windowEnd)
            ->with('child.parents')
            ->get()
            ->groupBy(function ($checkIn) {
                // Gruppiere nach Elternteil + lock_at Datum
                $parentIds = $checkIn->child?->parents?->pluck('id')->join('_') ?? 'none';
                return $parentIds . '_' . $checkIn->lock_at->format('Y-m-d');
            });

        foreach ($openCheckIns as $groupKey => $checkIns) {
            $firstCheckIn = $checkIns->first();
            $deadline = $firstCheckIn->lock_at;

            if (!$firstCheckIn->child || !$firstCheckIn->child->parents) {
                continue;
            }

            $parents = $firstCheckIn->child->parents;

            foreach ($parents as $parent) {
                $level = $this->determineLevel($settings, $deadline, $now);
                if ($level === null) {
                    continue;
                }

                // Prüfe ob bereits erinnert für dieses Level
                $alreadySent = ReminderLog::where('remindable_type', ChildCheckIn::class)
                    ->where('remindable_id', $firstCheckIn->id)
                    ->where('user_id', $parent->id)
                    ->where('level', $level)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                // Sammle Kindernamen für die Nachricht
                $childNames = $checkIns->pluck('child.first_name')->unique()->filter()->join(', ');
                $dateRange = $checkIns->min('date')->format('d.m.') . '–' . $checkIns->max('date')->format('d.m.Y');

                $this->sendAttendanceReminder($parent, $firstCheckIn, $level, $settings, $deadline, $childNames, $dateRange);
                $count++;
            }
        }

        return $count;
    }

    // ═══════════════════════════════════════════════════════════════
    //  Hilfsmethoden
    // ═══════════════════════════════════════════════════════════════

    /**
     * Bestimmt die aktuelle Erinnerungsstufe basierend auf dem Zeitpunkt.
     * Alle drei Stufen feuern VOR dem Fristablauf.
     * Gibt null zurück, wenn keine Stufe ausgelöst werden soll.
     *
     * Wichtig: Vergleich über startOfDay(), damit date-only Spalten (z.B. `rueckmeldungen.ende`,
     * `child_check_ins.lock_at`) korrekt behandelt werden. Diese werden als Mitternacht
     * zurückgegeben, weshalb ein datetime-basierter Vergleich falsche Werte liefern würde.
     */
    private function determineLevel(ReminderSetting $s, Carbon $deadline, Carbon $now): ?int
    {
        // Normalisierung auf Tagesbeginn: verhindert falsche Ergebnisse bei date-only Feldern
        // (z.B. Ende = "2026-04-10 00:00:00" wenn als date gespeichert, now = "2026-04-09 15:30")
        $daysUntilDeadline = (int) $now->copy()->startOfDay()->diffInDays(
            $deadline->copy()->startOfDay(),
            false  // negativ = Frist bereits überschritten
        );

        // Nach Fristablauf: keine Erinnerungen mehr
        if ($daysUntilDeadline < 0) {
            return null;
        }

        // Stufe 3: Am Fristtag (oder bis zu N Tage davor) – letzte Erinnerung + Eskalation
        // Wird zuerst geprüft, damit sie Vorrang vor Stufe 2 hat
        if ($s->level3_active && $daysUntilDeadline <= $s->level3_days_before_deadline) {
            return 3;
        }

        // Stufe 2: Kurz vor Ablauf
        if ($s->level2_active && $daysUntilDeadline <= $s->level2_days_before_deadline) {
            return 2;
        }

        // Stufe 1: Einige Tage vor Ablauf (nur wenn Stufe 2 noch nicht greift)
        if ($s->level1_active && $daysUntilDeadline <= $s->level1_days_before_deadline) {
            if (! $s->level2_active || $daysUntilDeadline > $s->level2_days_before_deadline) {
                return 1;
            }
        }

        return null;
    }

    /**
     * Versendet eine Erinnerung über die konfigurierten Kanäle.
     */
    private function sendReminder(
        User $user,
        Post $post,
        $remindable,
        int $level,
        ReminderSetting $s,
        Carbon $deadline,
        string $type
    ): void {
        $levelKey = "level{$level}";
        $channels = [];

        // In-App Benachrichtigung
        if ($s->{"{$levelKey}_in_app"}) {
            $this->sendInAppReminder($user, $post, $level, $deadline, $type);
            $channels[] = 'in_app';
        }

        // E-Mail
        if ($s->{"{$levelKey}_email"}) {
            $this->sendEmailReminder($user, $post, $level, $deadline, $type);
            $channels[] = 'email';
        }

        // Push-Benachrichtigung
        if ($s->{"{$levelKey}_push"}) {
            $this->sendPushReminder($user, $post, $level, $deadline, $type);
            $channels[] = 'push';
        }

        // Eskalation (nur Stufe 3)
        if ($level === 3 && $s->level3_escalate_to_author) {
            $this->escalateToAuthor($post, $user, $deadline, $type);
            $channels[] = 'escalation';
        }

        // Log erstellen (ein Eintrag pro Kanal)
        foreach ($channels as $channel) {
            ReminderLog::create([
                'remindable_type' => get_class($remindable),
                'remindable_id' => $remindable->id,
                'user_id' => $user->id,
                'post_id' => $post->id,
                'level' => $level,
                'channel' => $channel,
                'sent_at' => now(),
            ]);
        }

        Log::info("[ProcessRemindersJob] Erinnerung gesendet", [
            'user' => $user->id,
            'post' => $post->id,
            'level' => $level,
            'type' => $type,
            'channels' => $channels,
        ]);
    }

    /**
     * Versendet eine Anwesenheitsabfrage-Erinnerung.
     */
    private function sendAttendanceReminder(
        User $parent,
        ChildCheckIn $checkIn,
        int $level,
        ReminderSetting $s,
        Carbon $deadline,
        string $childNames,
        string $dateRange
    ): void {
        $levelKey = "level{$level}";
        $channels = [];

        $title = match ($level) {
            1 => 'Anwesenheitsabfrage: Rückmeldung benötigt',
            2 => '⚠ Erinnerung: Anwesenheitsabfrage läuft bald ab',
            3 => '🔴 Letzte Chance: Anwesenheitsabfrage – Frist läuft heute ab',
        };

        $body = count(explode(', ', $childNames)) > 1
            ? "Die Anwesenheitsabfrage für Ihre Kinder ({$childNames}) für den Zeitraum {$dateRange} erfordert Ihre Rückmeldung bis {$deadline->format('d.m.Y')}."
            : "Die Anwesenheitsabfrage für {$childNames} für den Zeitraum {$dateRange} erfordert Ihre Rückmeldung bis {$deadline->format('d.m.Y')}.";

        // In-App
        if ($s->{"{$levelKey}_in_app"}) {
            Notification::create([
                'user_id' => $parent->id,
                'message' => $body,
                'title' => $title,
                'url' => url('schickzeiten'),
                'type' => 'Anwesenheitsabfrage',
            ]);
            $channels[] = 'in_app';
        }

        // E-Mail
        if ($s->{"{$levelKey}_email"} && $parent->email) {
            try {
                Mail::to($parent->email)->send(new ReminderMail(
                    userName: $parent->name,
                    postTitle: "Anwesenheitsabfrage ({$childNames})",
                    postId: 0,
                    deadline: $deadline->format('d.m.Y'),
                    level: $level,
                    type: 'anwesenheit'
                ));
            } catch (\Exception $e) {
                Log::error("[ProcessRemindersJob] E-Mail-Fehler (Anwesenheit): " . $e->getMessage());
            }
            $channels[] = 'email';
        }

        // Push
        if ($s->{"{$levelKey}_push"}) {
            try {
                $parent->notify(new ReminderPushNotification(
                    $title,
                    $body,
                    url('schickzeiten')
                ));
            } catch (\Exception $e) {
                Log::error("[ProcessRemindersJob] Push-Fehler (Anwesenheit): " . $e->getMessage());
            }
            $channels[] = 'push';
        }

        // Log erstellen
        foreach ($channels as $channel) {
            ReminderLog::create([
                'remindable_type' => ChildCheckIn::class,
                'remindable_id' => $checkIn->id,
                'user_id' => $parent->id,
                'post_id' => null,
                'level' => $level,
                'channel' => $channel,
                'sent_at' => now(),
            ]);
        }

        Log::info("[ProcessRemindersJob] Anwesenheits-Erinnerung gesendet", [
            'parent' => $parent->id,
            'children' => $childNames,
            'level' => $level,
            'channels' => $channels,
        ]);
    }

    private function sendInAppReminder(User $user, Post $post, int $level, Carbon $deadline, string $type): void
    {
        $typeLabel = match ($type) {
            'lesebestaetigung' => 'Lesebestätigung',
            default => 'Rückmeldung',
        };

        $header = $post->header;
        $frist = $deadline->format('d.m.Y');

        $title = match ($level) {
            1 => 'Erinnerung: ' . $typeLabel . ' ausstehend',
            2 => 'Dringend: ' . $typeLabel . ' fehlt',
            3 => 'Letzte Erinnerung: ' . $typeLabel . ' fällig',
        };

        $message = match ($level) {
            1 => 'Bitte geben Sie Ihre ' . $typeLabel . ' für ' . $header . ' bis zum ' . $frist . ' ab.',
            2 => 'Die Frist für Ihre ' . $typeLabel . ' zu ' . $header . ' läuft am ' . $frist . ' ab!',
            3 => 'Die Frist für Ihre ' . $typeLabel . ' zu ' . $header . ' läuft heute (' . $frist . ') ab! Bitte antworten Sie jetzt.',
        };

        Notification::create([
            'user_id' => $user->id,
            'message' => $message,
            'title' => $title,
            'url' => url('post/' . $post->id),
            'type' => $typeLabel,
        ]);
    }

    private function sendEmailReminder(User $user, Post $post, int $level, Carbon $deadline, string $type): void
    {
        if (!$user->email) {
            return;
        }

        try {
            Mail::to($user->email)->send(new ReminderMail(
                userName: $user->name,
                postTitle: $post->header,
                postId: $post->id,
                deadline: $deadline->format('d.m.Y'),
                level: $level,
                type: $type
            ));
        } catch (\Exception $e) {
            Log::error("[ProcessRemindersJob] E-Mail-Fehler: " . $e->getMessage(), [
                'user' => $user->id,
                'post' => $post->id,
            ]);
        }
    }

    private function sendPushReminder(User $user, Post $post, int $level, Carbon $deadline, string $type): void
    {
        $typeLabel = match ($type) {
            'lesebestaetigung' => 'Lesebestätigung',
            'anwesenheit' => 'Anwesenheitsabfrage',
            default => 'Rückmeldung',
        };

        $title = match ($level) {
            1 => "Erinnerung: {$typeLabel} ausstehend",
            2 => "Dringend: {$typeLabel} fehlt",
            3 => "Letzte Erinnerung: {$typeLabel} fällig",
        };

        $header = $post->header;
        $frist = $deadline->format('d.m.Y');

        $body = match ($level) {
            1 => $typeLabel . ' für „' . $header . '" bis ' . $frist . ' abgeben.',
            2 => 'Frist für ' . $typeLabel . ' zu „' . $header . '" läuft am ' . $frist . ' ab!',
            3 => 'Frist für ' . $typeLabel . ' zu „' . $header . '" läuft heute (' . $frist . ') ab!',
        };

        $actionUrl = $type === 'anwesenheit'
            ? url('schickzeiten')
            : url('post/' . $post->id);

        try {
            $user->notify(new ReminderPushNotification($title, $body, $actionUrl));
        } catch (\Exception $e) {
            Log::error("[ProcessRemindersJob] Push-Fehler: " . $e->getMessage(), [
                'user' => $user->id,
            ]);
        }
    }

    private function escalateToAuthor(Post $post, User $user, Carbon $deadline, string $type): void
    {
        $author = $post->autor;

        if (!$author || !$author->email) {
            return;
        }

        // In-App-Benachrichtigung an den Autor
        $typeLabel = match ($type) {
            'lesebestaetigung' => 'Lesebestätigung',
            default => 'Rückmeldung',
        };

        $header = $post->header;
        $frist = $deadline->format('d.m.Y');

        Notification::create([
            'user_id' => $author->id,
            'message' => $typeLabel . ' von ' . $user->name . ' für ' . $header . ' ist trotz mehrfacher Erinnerung bis heute (' . $frist . ') nicht eingegangen.',
            'title' => 'Eskalation: ' . $typeLabel . ' fällig heute',
            'url' => url('post/' . $post->id),
            'type' => 'Eskalation',
            'important' => true,
        ]);

        // E-Mail an den Autor
        try {
            Mail::to($author->email)->send(new ReminderEscalationMail(
                authorName: $author->name,
                postTitle: $post->header,
                postId: $post->id,
                userName: $user->name,
                deadline: $deadline->format('d.m.Y'),
                type: $type
            ));
        } catch (\Exception $e) {
            Log::error("[ProcessRemindersJob] Eskalations-E-Mail-Fehler: " . $e->getMessage());
        }
    }
}




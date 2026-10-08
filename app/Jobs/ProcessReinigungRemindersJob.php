<?php

namespace App\Jobs;

use App\Mail\ReinigungReminderMail;
use App\Model\Notification;
use App\Model\Reinigung;
use App\Model\ReminderLog;
use App\Model\User;
use App\Services\App\Modules;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationPreferences;
use App\Settings\ReinigungSetting;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Erinnert Familien X Tage (ReinigungSetting::$reminder_days_before) vor ihrem
 * eigenen Reinigungseinsatz per E-Mail und/oder Push-Benachrichtigung.
 * Der Versand wird je Nutzer/Kanal über ReminderLog dedupliziert, damit bei
 * täglichem Lauf keine doppelten Erinnerungen verschickt werden.
 */
class ProcessReinigungRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $settings = new ReinigungSetting;

        if (! $settings->reminder_enabled) {
            return;
        }

        $targetDate = Carbon::now()->addDays($settings->reminder_days_before)->startOfDay();

        $reinigungen = Reinigung::query()
            ->whereDate('datum', $targetDate->toDateString())
            ->whereNotNull('users_id')
            ->with('user')
            ->get();

        foreach ($reinigungen as $reinigung) {
            if (is_null($reinigung->user)) {
                continue;
            }

            // Alle Familienmitglieder erinnern (FamilyResolver: legacy = sorg2-Partner)
            $familyMembers = User::query()->whereIn('id', $reinigung->user->familyUserIds())->get();
            foreach ($familyMembers as $member) {
                if (! Modules::isActiveFor($member, 'Reinigung')) {
                    continue;
                }
                $this->remindUser($member, $reinigung, $settings);
            }
        }
    }

    private function remindUser(User $user, Reinigung $reinigung, ReinigungSetting $settings): void
    {
        $woche = $reinigung->datum->copy()->startOfWeek()->format('d.m.').' - '.$reinigung->datum->copy()->endOfWeek()->format('d.m.Y');

        if ($settings->reminder_email and $user->email and ! $this->alreadySent($user, $reinigung, 'email')
            and NotificationPreferences::allows($user, NotificationCategory::ORGANISATION, 'mail')) {
            Mail::to($user->email)->queue(new ReinigungReminderMail($user->name, $reinigung->aufgabe, $woche, $reinigung->bereich));
            $this->logReminder($user, $reinigung, 'email');
        }

        if ($settings->reminder_push and ! $this->alreadySent($user, $reinigung, 'push')) {
            // Glocke + App-/Browser-Push (created-Event, Kanäle nach Nutzereinstellung)
            Notification::create([
                'user_id' => $user->id,
                'type' => 'Reinigung',
                'title' => 'Erinnerung: Reinigungsdienst',
                'message' => 'Reinigungsdienst "'.($reinigung->aufgabe ?: 'Reinigungsdienst').'" in der Woche '.$woche,
                'url' => url('reinigung'),
            ]);
            $this->logReminder($user, $reinigung, 'push');
        }
    }

    private function alreadySent(User $user, Reinigung $reinigung, string $channel): bool
    {
        return ReminderLog::forRemindable(Reinigung::class, $reinigung->id)
            ->forUser($user->id)
            ->where('channel', $channel)
            ->exists();
    }

    private function logReminder(User $user, Reinigung $reinigung, string $channel): void
    {
        ReminderLog::create([
            'remindable_type' => Reinigung::class,
            'remindable_id' => $reinigung->id,
            'user_id' => $user->id,
            'post_id' => null,
            'level' => 1,
            'channel' => $channel,
            'sent_at' => now(),
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\Notification;
use App\Model\User;
use App\Notifications\AttendanceQueryNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAttendanceQueryReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Führe den Job aus.
     *
     * Sendet Erinnerungen an Eltern, deren Kinder Anwesenheitsabfragen haben,
     * die in 3 Tagen ablaufen.
     */
    public function handle(): void
    {
        // Datum in 3 Tagen
        $reminderDate = Carbon::now()->addDays(3)->toDateString();

        Log::info('Sende Anwesenheitsabfragen-Erinnerungen für Datum: '.$reminderDate);

        // Finde alle CheckIns, die in 3 Tagen ablaufen (lock_at)
        $checkIns = ChildCheckIn::query()
            ->whereDate('lock_at', $reminderDate)
            ->whereNull('should_be') // Noch nicht beantwortet
            ->with(['child.parents'])
            ->get();

        // Gruppiere nach Eltern und Datum, um nur eine Benachrichtigung pro Elternteil zu senden
        $parentNotifications = [];

        foreach ($checkIns as $checkIn) {
            if (!$checkIn->child) {
                continue;
            }

            foreach ($checkIn->child->parents as $parent) {
                $parentId = $parent->id;
                $lockDate = $checkIn->lock_at->format('d.m.Y');

                // Prüfe, ob dieser Elternteil bereits eine Benachrichtigung für dieses Ablaufdatum hat
                $notificationKey = $parentId . '_' . $lockDate;

                if (!isset($parentNotifications[$notificationKey])) {
                    $parentNotifications[$notificationKey] = [
                        'parent' => $parent,
                        'lock_date' => $checkIn->lock_at,
                        'children' => [],
                    ];
                }

                // Füge das Kind zur Liste hinzu, wenn es noch nicht vorhanden ist
                if (!in_array($checkIn->child->first_name . ' ' . $checkIn->child->last_name, $parentNotifications[$notificationKey]['children'])) {
                    $parentNotifications[$notificationKey]['children'][] = $checkIn->child->first_name . ' ' . $checkIn->child->last_name;
                }
            }
        }

        // Sende Benachrichtigungen
        foreach ($parentNotifications as $data) {
            $parent = $data['parent'];
            $lockDate = $data['lock_date']->format('d.m.Y');
            $childrenNames = implode(', ', $data['children']);

            $title = 'Erinnerung: Anwesenheitsabfrage läuft ab';
            $body = count($data['children']) > 1
                ? "Die Anwesenheitsabfrage für Ihre Kinder ({$childrenNames}) läuft am {$lockDate} ab. Bitte geben Sie Ihre Rückmeldung."
                : "Die Anwesenheitsabfrage für {$childrenNames} läuft am {$lockDate} ab. Bitte geben Sie Ihre Rückmeldung.";

            // Erstelle Datenbank-Notification
            Notification::create([
                'user_id' => $parent->id,
                'message' => $body,
                'title' => $title,
                'url' => url('schickzeiten'),
                'type' => 'Anwesenheitsabfrage',
            ]);

            // Sende Push-Notification
            try {
                $parent->notify(new AttendanceQueryNotification($title, $body));
                Log::info("Anwesenheitsabfragen-Erinnerung an {$parent->name} (ID: {$parent->id}) gesendet");
            } catch (\Exception $e) {
                Log::error("Fehler beim Senden der Anwesenheitsabfragen-Erinnerung an {$parent->name}: " . $e->getMessage());
            }
        }

        Log::info('Anwesenheitsabfragen-Erinnerungen versendet: ' . count($parentNotifications) . ' Benachrichtigungen');
    }
}



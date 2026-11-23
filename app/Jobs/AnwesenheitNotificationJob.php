<?php

namespace App\Jobs;

use App\Model\ChildCheckIn;
use App\Model\Notification;
use App\Model\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AnwesenheitNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $parent;

    public $childName;

    public $type;

    public ?int $childId = null;

    /** Zeitpunkt der An-/Abmeldung (nicht der Verarbeitung in der Queue). */
    public ?string $occurredAt = null;

    /**
     * Erstelle eine neue Instanz des Jobs.
     *
     * @param  User  $parent  Das Elternteil
     * @param  string  $childName  Vorname des Kindes
     * @param  string  $type  'checkIn' oder 'checkOut'
     * @param  int|null  $childId  Kind, dessen Status vor dem Versand erneut geprüft wird
     * @param  CarbonInterface|null  $occurredAt  Zeitpunkt der An-/Abmeldung
     */
    public function __construct(User $parent, string $childName, string $type, ?int $childId = null, ?CarbonInterface $occurredAt = null)
    {
        $this->parent = $parent;
        $this->childName = $childName;
        $this->type = $type;
        $this->childId = $childId;
        $this->occurredAt = ($occurredAt ?? now())->toIso8601String();
    }

    /**
     * Führe den Job aus.
     */
    public function handle(): void
    {
        // Die Queue läuft ggf. zeitversetzt (Cronjob). Wurde der Status inzwischen wieder
        // geändert (z. B. versehentliche Abmeldung sofort korrigiert), ist die Meldung
        // überholt und würde Eltern verwirren – dann nicht mehr versenden.
        if ($this->isOutdated()) {
            Log::info('Anwesenheits-Benachrichtigung verworfen, Status hat sich zwischenzeitlich geändert.', [
                'child_id' => $this->childId,
                'type' => $this->type,
                'user_id' => $this->parent->id,
            ]);

            return;
        }

        $occurredAt = $this->occurredAtLocal()->format('d.m.Y H:i');

        if ($this->type == 'checkIn') {
            Notification::create([
                'user_id' => $this->parent->id, // Elternteil-ID
                'message' => "Ihr Kind {$this->childName} wurde erfolgreich angemeldet.",
                'title' => "Anmeldung am {$occurredAt}",
                'url' => url('schickzeiten'),
                'type' => 'Anwesenheit',
            ]);
        } else {
            Notification::create([
                'user_id' => $this->parent->id, // Elternteil-ID
                'message' => "Ihr Kind {$this->childName} wurde erfolgreich abgemeldet.",
                'title' => "Abmeldung am {$occurredAt}",
                'url' => url('schickzeiten'),
                'type' => 'Anwesenheit',
            ]);
        }

    }

    private function isOutdated(): bool
    {
        if ($this->childId === null) {
            return false;
        }

        $checkIn = ChildCheckIn::query()
            ->where('child_id', $this->childId)
            ->whereDate('date', $this->occurredAtLocal()->toDateString())
            ->first();

        $isPresent = $checkIn && $checkIn->checked_in && ! $checkIn->checked_out;

        return $this->type == 'checkIn' ? ! $isPresent : $isPresent;
    }

    /** Bereits vor diesem Update eingereihte Jobs haben keinen Zeitstempel – dann Verarbeitungszeit. */
    private function occurredAtLocal(): Carbon
    {
        return $this->occurredAt
            ? Carbon::parse($this->occurredAt)->timezone(config('app.timezone'))
            : now();
    }
}

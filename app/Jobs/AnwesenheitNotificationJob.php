<?php

namespace App\Jobs;

use App\Model\Notification;
use App\Model\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnwesenheitNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $parent;
    public $childName;

    public $type;



    /**
     * Erstelle eine neue Instanz des Jobs.
     *
     * @param User $parent Das Elternteil
     * @param string $childName Name des abgemeldeten Kindes
     */
    public function __construct(User $parent, string $childName, string $type)
    {
        $this->parent = $parent;
        $this->childName = $childName;
        $this->type = $type;
    }

    /**
     * Führe den Job aus.
     *
     * @return void
     */
    public function handle()
    {
        $currentDateTime = now()->format('d.m.Y H:i');
        if ($this->type == 'checkIn') {
            Notification::create([
                'user_id' => $this->parent->id, // Elternteil-ID
                'message' => "Ihr Kind {$this->childName} wurde erfolgreich angemeldet.",
                'title' => "Anmeldung am {$currentDateTime}",
                'url' => url('schickzeiten'),
                'type' => 'Anwesenheit',
            ]);
        } else {
            Notification::create([
                'user_id' => $this->parent->id, // Elternteil-ID
                'message' => "Ihr Kind {$this->childName} wurde erfolgreich abgemeldet.",
                'title' => "Abmeldung am {$currentDateTime}",
                'url' => url('schickzeiten'),
                'type' => 'Anwesenheit',
            ]);
        }

    }
}

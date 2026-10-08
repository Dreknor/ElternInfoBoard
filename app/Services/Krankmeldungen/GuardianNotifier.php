<?php

namespace App\Services\Krankmeldungen;

use App\Mail\KrankmeldungInfoMail;
use App\Model\Child;
use App\Model\Krankmeldungen;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use App\Settings\NotifySetting;
use App\Traits\NotificationTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationPreferences;

/**
 * Informiert bei einer Krankmeldung die übrigen Bezugspersonen des Kindes
 * (Spezifikation 2.4) – z. B. den getrennt lebenden Elternteil – damit nicht
 * doppelt krankgemeldet wird. Nur Personen mit Zugriff auf Gesundheitsdaten
 * (Policy viewHealth) und Informationsrecht; abschaltbar per NotifySetting.
 */
class GuardianNotifier
{
    use NotificationTrait;

    public function __construct(private readonly FamilyResolver $resolver) {}

    /**
     * @return Collection<int, User> benachrichtigte Personen
     */
    public function notifyOthers(Krankmeldungen $krankmeldung, User $reporter): Collection
    {
        $none = new Collection;

        if (! $krankmeldung->child_id || ! $this->enabled()) {
            return $none;
        }

        $child = Child::find($krankmeldung->child_id);
        if (! $child) {
            return $none;
        }

        $recipients = $this->resolver->guardiansFor($child)
            ->reject(fn (User $guardian) => $guardian->id === $reporter->id)
            ->filter(fn (User $guardian) => $guardian->pivot === null || (bool) ($guardian->pivot->receives_information ?? true))
            ->filter(fn (User $guardian) => $guardian->can('viewHealth', $child))
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return $none;
        }

        $zeitraum = $krankmeldung->start->format('d.m.Y').' – '.$krankmeldung->ende->format('d.m.Y');
        // Bewusst ohne Kindername und Zeitraum: der Text erscheint auch als Push auf dem
        // Sperrbildschirm (keine Kinder-/Gesundheitsdaten in Push-Texten)
        $this->notify(
            $recipients,
            'Krankmeldung eingegangen',
            $reporter->name.' hat eine Krankmeldung eingetragen. Die Schule ist informiert.',
            false,
            url('krankmeldung'),
            'Krankmeldung',
        );

        foreach ($recipients as $recipient) {
            if (! $recipient->email || ! NotificationPreferences::allows($recipient, NotificationCategory::HORT, 'mail')) {
                continue;
            }
            try {
                Mail::to($recipient->email)->queue(new KrankmeldungInfoMail($recipient->name, $reporter->name, $child, $zeitraum));
            } catch (\Throwable $e) {
                Log::warning('Krankmeldung: Info an Bezugsperson fehlgeschlagen: '.$e->getMessage());
            }
        }

        return $recipients;
    }

    private function enabled(): bool
    {
        try {
            return (bool) app(NotifySetting::class)->krankmeldung_notify_guardians;
        } catch (\Throwable) {
            // Setting noch nicht migriert: Standard „an“
            return true;
        }
    }
}

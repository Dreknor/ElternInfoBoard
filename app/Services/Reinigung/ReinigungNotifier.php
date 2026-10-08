<?php

namespace App\Services\Reinigung;

use App\Mail\ReinigungChangeMail;
use App\Model\Notification;
use App\Model\Reinigung;
use App\Model\User;
use App\Services\App\Modules;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationPreferences;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Benachrichtigt Familien über Änderungen am Reinigungsplan, die sie betreffen
 * (neue Einteilung, entfernte Einteilung, automatisch erstellter Plan).
 *
 * Es wird jeweils die ganze Familie (FamilyResolver) benachrichtigt:
 * - Glocke + App-/Browser-Push (über Notification::created, Kanäle nach Nutzereinstellung)
 * - E-Mail, sofern für die Kategorie "Reinigung, Pflichtstunden & AGs" erlaubt
 * Vergangene Einsätze, die handelnde Person selbst und Nutzer, für die das Modul
 * "Reinigung" nicht aktiv ist, werden nicht benachrichtigt.
 */
class ReinigungNotifier
{
    public function assigned(Reinigung $reinigung): void
    {
        if (! $this->isRelevant($reinigung)) {
            return;
        }

        $this->notifyFamily(
            $reinigung->user,
            'Reinigungsplan: neuer Einsatz',
            'Sie wurden für die Woche '.$this->woche($reinigung).' eingeteilt: '.$this->aufgabe($reinigung).$this->bemerkung($reinigung),
            'Sie wurden im Reinigungsplan für folgenden Einsatz eingeteilt:',
            collect([$reinigung])
        );
    }

    public function removed(Reinigung $reinigung): void
    {
        if (! $this->isRelevant($reinigung)) {
            return;
        }

        $this->notifyFamily(
            $reinigung->user,
            'Reinigungsplan: Einsatz entfernt',
            'Ihr Einsatz "'.$this->aufgabe($reinigung).'" in der Woche '.$this->woche($reinigung).' wurde aus dem Reinigungsplan entfernt.',
            'folgender Einsatz wurde aus dem Reinigungsplan entfernt - Sie müssen diesen Dienst nicht mehr übernehmen:',
            collect([$reinigung])
        );
    }

    /**
     * Fasst mehrere neue Einsätze (z. B. aus der automatischen Planerstellung) je
     * Familie zu einer Benachrichtigung zusammen.
     *
     * @param  Collection<int, Reinigung>  $reinigungen
     */
    public function assignedMany(Collection $reinigungen): void
    {
        $reinigungen
            ->filter(fn (Reinigung $reinigung) => $this->isRelevant($reinigung))
            ->groupBy(fn (Reinigung $reinigung) => $this->familyKey($reinigung->user))
            ->each(function (Collection $familyEntries) {
                $sorted = $familyEntries->sortBy(fn (Reinigung $r) => $r->datum->timestamp)->values();

                if ($sorted->count() === 1) {
                    $this->assigned($sorted->first());

                    return;
                }

                $lines = $sorted->map(fn (Reinigung $r) => $this->woche($r).': '.$this->aufgabe($r))->implode("\n");

                $this->notifyFamily(
                    $sorted->first()->user,
                    'Reinigungsplan: neue Einsätze',
                    'Sie wurden für '.$sorted->count()." Einsätze eingeteilt:\n".$lines,
                    'Sie wurden im Reinigungsplan für folgende Einsätze eingeteilt:',
                    $sorted
                );
            });
    }

    private function isRelevant(Reinigung $reinigung): bool
    {
        return $reinigung->user !== null
            && $reinigung->datum->copy()->endOfWeek()->gte(Carbon::now()->startOfDay());
    }

    /**
     * @param  Collection<int, Reinigung>  $einsaetze
     */
    private function notifyFamily(User $user, string $title, string $message, string $mailIntro, Collection $einsaetze): void
    {
        $actorId = auth()->id();
        $mailEinsaetze = $einsaetze->map(fn (Reinigung $r) => [
            'woche' => $this->woche($r),
            'aufgabe' => $this->aufgabe($r),
            'bemerkungen' => $r->bemerkungPunkte(),
        ])->all();

        User::query()
            ->whereIn('id', $user->familyUserIds())
            ->get()
            ->each(function (User $member) use ($actorId, $title, $message, $mailIntro, $mailEinsaetze) {
                // Dashboard-Hinweis (ReinigungComposer) ist tageweise gecacht
                Cache::forget('reinigung'.$member->id);

                if ($member->id === $actorId || ! Modules::isActiveFor($member, 'Reinigung')) {
                    return;
                }

                Notification::create([
                    'user_id' => $member->id,
                    'type' => 'Reinigung',
                    'title' => $title,
                    'message' => $message,
                    'url' => url('reinigung'),
                ]);

                if ($member->email && NotificationPreferences::allows($member, NotificationCategory::ORGANISATION, 'mail')) {
                    Mail::to($member->email)->queue(new ReinigungChangeMail($member->name, $title, $mailIntro, $mailEinsaetze));
                }
            });
    }

    private function familyKey(User $user): string
    {
        $ids = $user->familyUserIds();
        sort($ids);

        return implode('-', $ids);
    }

    private function woche(Reinigung $reinigung): string
    {
        return $reinigung->weekStart()->format('d.m.').' - '.$reinigung->weekEnd()->format('d.m.Y');
    }

    private function aufgabe(Reinigung $reinigung): string
    {
        return $reinigung->aufgabe ?: 'Reinigungsdienst';
    }

    private function bemerkung(Reinigung $reinigung): string
    {
        return $reinigung->bemerkung ? ' (Bemerkung: '.implode(', ', $reinigung->bemerkungPunkte()).')' : '';
    }
}

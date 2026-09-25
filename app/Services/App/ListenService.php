<?php

namespace App\Services\App;

use App\Mail\TerminAbsageEltern;
use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Model\listen_termine;
use App\Model\User;
use App\Notifications\Push;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Listen: Terminbuchungen und Eintragungen (B-31, B-32).
 * Buchungen sind atomar (keine Doppelbuchung) und gelten für die ganze Familie.
 */
class ListenService
{
    public function canAccess(User $user, Liste $liste): bool
    {
        if ($user->can('edit terminliste') || (int) $liste->besitzer === $user->id) {
            return true;
        }

        return DB::table('group_listen')
            ->join('group_user', 'group_listen.group_id', '=', 'group_user.group_id')
            ->where('group_listen.liste_id', $liste->id)
            ->where('group_user.user_id', $user->id)
            ->exists();
    }

    public function requireOpen(User $user, Liste $liste, string $type): void
    {
        if (! $this->canAccess($user, $liste)) {
            throw new HttpException(403, 'Sie haben keinen Zugriff auf diese Liste.');
        }
        if ($liste->type !== $type) {
            throw new HttpException(422, 'Diese Aktion ist für diese Liste nicht möglich.');
        }
        if (! $liste->active || ($liste->ende && $liste->ende->copy()->endOfDay()->isPast())) {
            throw new HttpException(410, 'Die Liste ist abgelaufen oder nicht mehr aktiv.');
        }
    }

    public function familyTerminCount(User $user, Liste $liste): int
    {
        return listen_termine::where('listen_id', $liste->id)
            ->whereIn('reserviert_fuer', Family::userIds($user))
            ->count();
    }

    public function familyEintragCount(User $user, Liste $liste): int
    {
        return Listen_Eintragungen::where('listen_id', $liste->id)
            ->whereIn('user_id', Family::userIds($user))
            ->count();
    }

    public function reserveTermin(User $user, listen_termine $termin): listen_termine
    {
        $liste = $termin->liste;
        $this->requireOpen($user, $liste, 'termin');
        if ($termin->termin && $termin->termin->isPast()) {
            throw new HttpException(410, 'Dieser Termin liegt in der Vergangenheit.');
        }
        if (! $liste->multiple && $this->familyTerminCount($user, $liste) > 0) {
            throw new HttpException(409, 'Ihre Familie hat in dieser Liste bereits einen Termin gebucht.');
        }

        // Atomar: nur buchen, wenn noch frei (verhindert Doppelbuchung bei gleichzeitigen Anfragen).
        $updated = listen_termine::where('id', $termin->id)
            ->whereNull('reserviert_fuer')
            ->update(['reserviert_fuer' => $user->id, 'updated_at' => now()]);
        if ($updated === 0) {
            throw new HttpException(409, 'Dieser Termin wurde gerade von jemand anderem gebucht.');
        }

        try {
            if ($liste->ersteller) {
                Notification::send($liste->ersteller, new Push(
                    $liste->listenname.': Termin vergeben',
                    $user->name.' hat den Termin '.$termin->termin->format('d.m.Y H:i').' reserviert.'
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Listen: Push an Ersteller fehlgeschlagen: '.$e->getMessage());
        }

        return $termin->fresh();
    }

    public function cancelTermin(User $user, listen_termine $termin, ?string $reason = null): void
    {
        $liste = $termin->liste;
        $allowed = $user->can('edit terminliste')
            || (int) $liste->besitzer === $user->id
            || in_array((int) $termin->reserviert_fuer, Family::userIds($user), true);
        if (! $termin->reserviert_fuer || ! $allowed) {
            throw new HttpException(403, 'Sie können diesen Termin nicht absagen.');
        }

        $booked = $termin->eingetragenePerson;
        $termin->update(['reserviert_fuer' => null]);

        // Wie im Web: Listen-Ersteller und eingetragene Person informieren.
        foreach (array_filter([$liste->ersteller, $booked]) as $recipient) {
            Mail::to($recipient->email, $recipient->name)
                ->queue(new TerminAbsageEltern($user, $liste, $termin->termin, $reason ?? ''));
        }
    }

    public function addEintrag(User $user, Liste $liste, string $text): Listen_Eintragungen
    {
        $this->requireOpen($user, $liste, 'eintrag');
        if (! $liste->multiple && $this->familyEintragCount($user, $liste) > 0) {
            throw new HttpException(409, 'Ihre Familie hat sich in dieser Liste bereits eingetragen.');
        }

        return Listen_Eintragungen::create([
            'listen_id' => $liste->id,
            'eintragung' => $text,
            'user_id' => $user->id,
            'created_by' => $user->id,
        ]);
    }

    public function reserveEintrag(User $user, Listen_Eintragungen $eintrag): void
    {
        $liste = $eintrag->liste;
        $this->requireOpen($user, $liste, 'eintrag');
        if (! $liste->multiple && $this->familyEintragCount($user, $liste) > 0) {
            throw new HttpException(409, 'Ihre Familie hat sich in dieser Liste bereits eingetragen.');
        }
        $updated = Listen_Eintragungen::where('id', $eintrag->id)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id, 'updated_at' => now()]);
        if ($updated === 0) {
            throw new HttpException(409, 'Dieser Eintrag wurde gerade von jemand anderem übernommen.');
        }
    }

    public function cancelEintrag(User $user, Listen_Eintragungen $eintrag): void
    {
        if (! in_array((int) $eintrag->user_id, Family::userIds($user), true)) {
            throw new HttpException(403, 'Sie können diesen Eintrag nicht austragen.');
        }
        // Selbst angelegte Einträge löschen, vorgegebene wieder freigeben.
        if (in_array((int) $eintrag->created_by, Family::userIds($user), true)) {
            $eintrag->delete();
        } else {
            $eintrag->update(['user_id' => null]);
        }
    }
}

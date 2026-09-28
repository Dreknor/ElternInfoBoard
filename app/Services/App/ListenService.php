<?php

namespace App\Services\App;

use App\Mail\TerminAbsageEltern;
use App\Model\Child;
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
 * Listen: Terminbuchungen und Eintragungen (B-31, B-32) – gemeinsam für Web und App-API.
 * Buchungen sind atomar (keine Doppelbuchung). Die Begrenzung „einmal buchbar“ gilt je
 * Familie oder – bei Listen mit booking_scope "child" – je Kind: dann gehört jede Buchung
 * zu einem Kind, und alle Bezugspersonen des Kindes sehen sie.
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

    /**
     * Kinder, für die $user in dieser Liste buchen kann: eigene Kinder (FamilyResolver),
     * bevorzugt die in den Gruppen der Liste.
     *
     * @return \Illuminate\Support\Collection<int, Child>
     */
    public function bookableChildren(User $user, Liste $liste): \Illuminate\Support\Collection
    {
        $children = Family::children($user)->loadMissing(['class', 'group'])->sortBy('first_name')->values();
        $groupIds = $liste->groups()->pluck('groups.id')->all();
        $inGroups = $children->filter(fn (Child $c) => in_array($c->class_id, $groupIds) || in_array($c->group_id, $groupIds))->values();

        return $inGroups->isNotEmpty() ? $inGroups : $children;
    }

    /**
     * Kind der Buchung bestimmen. Bei Listen je Kind ist ein Kind Pflicht (genau ein
     * buchbares Kind wird automatisch gewählt); sonst ist es optional (z. B. Elterngespräch).
     */
    public function resolveChild(User $user, Liste $liste, ?int $childId): ?Child
    {
        if ($childId === null) {
            if (! $liste->bookingPerChild()) {
                return null;
            }
            $bookable = $this->bookableChildren($user, $liste);
            if ($bookable->count() !== 1) {
                throw new HttpException(422, $bookable->isEmpty()
                    ? 'In dieser Liste wird je Kind gebucht – Ihrem Konto ist kein Kind zugeordnet.'
                    : 'Bitte wählen Sie aus, für welches Kind Sie buchen.');
            }

            return $bookable->first();
        }

        $child = $this->bookableChildren($user, $liste)->firstWhere('id', $childId)
            ?? Family::children($user)->firstWhere('id', $childId);
        if (! $child) {
            throw new HttpException(403, 'Für dieses Kind können Sie nicht buchen.');
        }

        return $child;
    }

    /** Buchungen je Kind (alle Bezugspersonen) bzw. je Familie prüfen. */
    public function assertLimit(User $user, Liste $liste, ?Child $child, ?string $kind = null): void
    {
        if ($liste->multiple) {
            return;
        }

        // Art der Buchung (Termin/Eintrag); Standard: Typ der Liste
        $kind ??= $liste->type;

        if ($child !== null) {
            $query = $kind === 'termin'
                ? listen_termine::where('listen_id', $liste->id)
                : Listen_Eintragungen::where('listen_id', $liste->id);
            if ($query->where('child_id', $child->id)->exists()) {
                throw new HttpException(409, 'Für dieses Kind ist in dieser Liste bereits gebucht.');
            }

            return;
        }

        if ($kind === 'termin' && $this->familyTerminCount($user, $liste) > 0) {
            throw new HttpException(409, 'Ihre Familie hat in dieser Liste bereits einen Termin gebucht.');
        }
        if ($kind === 'eintrag' && $this->familyEintragCount($user, $liste) > 0) {
            throw new HttpException(409, 'Ihre Familie hat sich in dieser Liste bereits eingetragen.');
        }
    }

    /** Darf $user eine Buchung (für ein Kind) absagen/austragen? */
    public function mayCancel(User $user, ?int $bookedBy, ?int $childId): bool
    {
        if ($bookedBy !== null && in_array($bookedBy, Family::userIds($user), true)) {
            return true;
        }

        return $childId !== null && Family::ownsChild($user, $childId);
    }

    public function reserveTermin(User $user, listen_termine $termin, ?int $childId = null): listen_termine
    {
        $liste = $termin->liste;
        $this->requireOpen($user, $liste, 'termin');
        if ($termin->termin && $termin->termin->isPast()) {
            throw new HttpException(410, 'Dieser Termin liegt in der Vergangenheit.');
        }
        $child = $this->resolveChild($user, $liste, $childId);
        $this->assertLimit($user, $liste, $child, 'termin');

        // Atomar: nur buchen, wenn noch frei (verhindert Doppelbuchung bei gleichzeitigen Anfragen).
        $updated = listen_termine::where('id', $termin->id)
            ->whereNull('reserviert_fuer')
            ->update(['reserviert_fuer' => $user->id, 'child_id' => $child?->id, 'updated_at' => now()]);
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
            || $this->mayCancel($user, $termin->reserviert_fuer ? (int) $termin->reserviert_fuer : null, $termin->child_id);
        if (! $termin->reserviert_fuer || ! $allowed) {
            throw new HttpException(403, 'Sie können diesen Termin nicht absagen.');
        }

        $booked = $termin->eingetragenePerson;
        $termin->update(['reserviert_fuer' => null, 'child_id' => null]);

        // Wie im Web: Listen-Ersteller und eingetragene Person informieren.
        foreach (array_filter([$liste->ersteller, $booked]) as $recipient) {
            Mail::to($recipient->email, $recipient->name)
                ->queue(new TerminAbsageEltern($user, $liste, $termin->termin, $reason ?? ''));
        }
    }

    public function addEintrag(User $user, Liste $liste, string $text, ?int $childId = null): Listen_Eintragungen
    {
        $this->requireOpen($user, $liste, 'eintrag');
        $child = $this->resolveChild($user, $liste, $childId);
        $this->assertLimit($user, $liste, $child, 'eintrag');

        return Listen_Eintragungen::create([
            'listen_id' => $liste->id,
            'eintragung' => $text,
            'user_id' => $user->id,
            'child_id' => $child?->id,
            'created_by' => $user->id,
        ]);
    }

    public function reserveEintrag(User $user, Listen_Eintragungen $eintrag, ?int $childId = null): void
    {
        $liste = $eintrag->liste;
        $this->requireOpen($user, $liste, 'eintrag');
        $child = $this->resolveChild($user, $liste, $childId);
        $this->assertLimit($user, $liste, $child, 'eintrag');
        $updated = Listen_Eintragungen::where('id', $eintrag->id)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id, 'child_id' => $child?->id, 'updated_at' => now()]);
        if ($updated === 0) {
            throw new HttpException(409, 'Dieser Eintrag wurde gerade von jemand anderem übernommen.');
        }
    }

    public function cancelEintrag(User $user, Listen_Eintragungen $eintrag): void
    {
        if (! $eintrag->user_id || ! $this->mayCancel($user, (int) $eintrag->user_id, $eintrag->child_id)) {
            throw new HttpException(403, 'Sie können diesen Eintrag nicht austragen.');
        }
        // Selbst angelegte Einträge löschen, vorgegebene wieder freigeben.
        if (in_array((int) $eintrag->created_by, Family::userIds($user), true)) {
            $eintrag->delete();
        } else {
            $eintrag->update(['user_id' => null, 'child_id' => null]);
        }
    }
}

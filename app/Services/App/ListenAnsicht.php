<?php

namespace App\Services\App;

use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Model\listen_termine;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Aufbereitung der Listen für die Web-Ansichten (Übersicht, Termin- und Eintragslisten).
 * Enthält keine Geschäftslogik – Buchungsregeln liegen im ListenService.
 */
class ListenAnsicht
{
    public function __construct(private readonly ListenService $listen) {}

    public function canManage(User $user, Liste $liste): bool
    {
        return $user->can('edit terminliste') || (int) $liste->besitzer === $user->id;
    }

    /** Darf $user die Namen der Buchenden sehen? */
    public function namesVisible(User $user, Liste $liste, bool $preview, bool $own = false): bool
    {
        if ($liste->visible_for_all) {
            return true;
        }

        return ! $preview && ($own || $this->canManage($user, $liste));
    }

    /** Gehört die Buchung zur Familie von $user (gebucht von einem Familienmitglied oder für ein eigenes Kind)? */
    public function isOwn(User $user, ?int $bookedBy, ?int $childId): bool
    {
        return $this->listen->mayCancel($user, $bookedBy, $childId);
    }

    /**
     * Frist als kurzer Text, z. B. „endet heute“, „noch 5 Tage“, „bis 24.11.2026“.
     *
     * @return array{text: string, urgent: bool}
     */
    public function frist(Liste $liste): array
    {
        if (! $liste->ende) {
            return ['text' => 'ohne Frist', 'urgent' => false];
        }

        $ende = $liste->ende->copy()->startOfDay();
        $tage = (int) Carbon::today()->diffInDays($ende, false);

        return match (true) {
            $tage < 0 => ['text' => 'abgelaufen', 'urgent' => true],
            $tage === 0 => ['text' => 'endet heute', 'urgent' => true],
            $tage === 1 => ['text' => 'endet morgen', 'urgent' => true],
            $tage <= 14 => ['text' => "noch {$tage} Tage", 'urgent' => $tage <= 3],
            default => ['text' => 'bis '.$ende->format('d.m.Y'), 'urgent' => false],
        };
    }

    /**
     * Belegung einer Liste: freie und insgesamt buchbare Plätze (bei Terminlisten nur künftige Termine).
     *
     * @return array{free: int, total: int}
     */
    public function belegung(Liste $liste): array
    {
        if ($liste->type === 'termin') {
            $kuenftig = $liste->termine->filter(fn (listen_termine $t) => $t->termin->greaterThanOrEqualTo(now()));

            return [
                'free' => $kuenftig->whereNull('reserviert_fuer')->count(),
                'total' => $kuenftig->count(),
            ];
        }

        return [
            'free' => $liste->eintragungen->whereNull('user_id')->count(),
            'total' => $liste->eintragungen->count(),
        ];
    }

    /**
     * Daten für die Übersicht /listen.
     *
     * @param  Collection<int, Liste>  $listen  alle für $user sichtbaren bzw. verwaltbaren aktuellen Listen
     */
    public function uebersicht(User $user, Collection $listen): array
    {
        $listen->loadMissing(['termine', 'eintragungen', 'groups']);
        $listen = $listen->sortBy(fn (Liste $l) => $l->ende?->timestamp ?? PHP_INT_MAX)->values();

        $empfaengerIds = $user->visibleListen()->pluck('listen.id')->all();

        $buchungen = collect();
        $karten = [];

        foreach ($listen as $liste) {
            $eigene = $this->eigeneBuchungen($user, $liste);
            foreach ($eigene as $buchung) {
                $buchungen->push(['liste' => $liste, 'buchung' => $buchung]);
            }

            $karten[$liste->id] = [
                'liste' => $liste,
                'frist' => $this->frist($liste),
                'belegung' => $this->belegung($liste),
                'gebucht' => $eigene->isNotEmpty(),
                'kannWeitereBuchen' => $eigene->isEmpty() || ! $this->buchbarkeit($user, $liste, $eigene)['gesperrt'],
                'canManage' => $this->canManage($user, $liste),
                'empfaenger' => in_array($liste->id, $empfaengerIds) && $liste->active,
            ];
        }

        $karten = collect($karten);
        $fuerMich = $karten->where('empfaenger', true);

        return [
            'buchungen' => $buchungen->sortBy(fn ($b) => $b['buchung'] instanceof listen_termine
                ? $b['buchung']->termin->timestamp
                : ($b['liste']->ende?->timestamp ?? PHP_INT_MAX))->values(),
            'terminKarten' => $fuerMich->filter(fn ($k) => $k['liste']->type === 'termin')->values(),
            'eintragKarten' => $fuerMich->filter(fn ($k) => $k['liste']->type !== 'termin')->values(),
            'verwaltungKarten' => $karten->where('canManage', true)->values(),
        ];
    }

    /**
     * Eigene (Familien-)Buchungen in einer Liste. Bei Terminlisten nur künftige Termine.
     *
     * @return Collection<int, listen_termine|Listen_Eintragungen>
     */
    public function eigeneBuchungen(User $user, Liste $liste): Collection
    {
        if ($liste->type === 'termin') {
            return $liste->termine
                ->filter(fn (listen_termine $t) => $t->reserviert_fuer !== null
                    && $t->termin->greaterThanOrEqualTo(now())
                    && $this->isOwn($user, (int) $t->reserviert_fuer, $t->child_id))
                ->sortBy('termin')
                ->values();
        }

        return $liste->eintragungen
            ->filter(fn (Listen_Eintragungen $e) => $e->user_id !== null
                && $this->isOwn($user, (int) $e->user_id, $e->child_id))
            ->values();
    }

    /**
     * Kann die Familie in dieser Liste (noch) buchen? Bei „je Kind“ werden nur Kinder ohne Buchung angeboten.
     *
     * @return array{gesperrt: bool, kinder: Collection}
     */
    public function buchbarkeit(User $user, Liste $liste, Collection $eigene): array
    {
        $kinder = $this->listen->bookableChildren($user, $liste);

        if ($liste->multiple) {
            return ['gesperrt' => false, 'kinder' => $kinder];
        }

        if ($liste->bookingPerChild()) {
            $gebuchteKinder = $eigene->pluck('child_id')->filter()->all();
            $offen = $kinder->reject(fn ($k) => in_array($k->id, $gebuchteKinder))->values();

            return ['gesperrt' => $kinder->isNotEmpty() && $offen->isEmpty(), 'kinder' => $offen];
        }

        $familie = $liste->type === 'termin'
            ? $this->listen->familyTerminCount($user, $liste)
            : $this->listen->familyEintragCount($user, $liste);

        return ['gesperrt' => $familie > 0, 'kinder' => $kinder];
    }

    /** Daten für die Detailseite einer Terminliste. */
    public function terminliste(User $user, Liste $liste, bool $preview): array
    {
        $liste->loadMissing(['termine.eingetragenePerson', 'termine.child', 'ersteller']);
        $canManage = ! $preview && $this->canManage($user, $liste);
        $eigene = $preview ? collect() : $this->eigeneBuchungen($user, $liste);
        $buchbarkeit = $preview
            ? ['gesperrt' => false, 'kinder' => $this->listen->bookableChildren($user, $liste)]
            : $this->buchbarkeit($user, $liste, $eigene);

        $tage = $liste->termine
            ->sortBy('termin')
            ->groupBy(fn (listen_termine $t) => $t->termin->format('Y-m-d'))
            ->map(function (Collection $termine, string $datum) {
                $kuenftig = $termine->filter(fn ($t) => $t->termin->greaterThanOrEqualTo(now()));

                return [
                    'datum' => Carbon::parse($datum),
                    'termine' => $termine->values(),
                    'vergangen' => $kuenftig->isEmpty(),
                    'frei' => $kuenftig->whereNull('reserviert_fuer')->count(),
                    'gesamt' => $kuenftig->count(),
                ];
            })
            ->values();

        $belegung = $this->belegung($liste);

        return [
            'canManage' => $canManage,
            'tage' => $tage,
            'eigeneTermine' => $eigene,
            'gesperrt' => $buchbarkeit['gesperrt'],
            'bookableChildren' => $buchbarkeit['kinder'],
            'frist' => $this->frist($liste),
            'frei' => $belegung['free'],
            'gesamt' => $belegung['total'],
            'allTermineInPast' => $liste->termine->isNotEmpty() && $belegung['total'] === 0,
        ];
    }

    /** Daten für die Detailseite einer Eintragsliste. */
    public function eintragliste(User $user, Liste $liste, bool $preview): array
    {
        $liste->loadMissing(['eintragungen.user', 'eintragungen.child', 'ersteller']);
        $canManage = ! $preview && $this->canManage($user, $liste);
        $eigene = $preview ? collect() : $this->eigeneBuchungen($user, $liste);
        $buchbarkeit = $preview
            ? ['gesperrt' => false, 'kinder' => $this->listen->bookableChildren($user, $liste)]
            : $this->buchbarkeit($user, $liste, $eigene);

        $eigeneIds = $eigene->pluck('id')->all();

        return [
            'canManage' => $canManage,
            'eigeneEintraege' => $eigene,
            'offen' => $liste->eintragungen->whereNull('user_id')->values(),
            'vergeben' => $liste->eintragungen
                ->whereNotNull('user_id')
                ->reject(fn ($e) => in_array($e->id, $eigeneIds))
                ->values(),
            'gesperrt' => $buchbarkeit['gesperrt'],
            'bookableChildren' => $buchbarkeit['kinder'],
            'frist' => $this->frist($liste),
            'darfEintragHinzufuegen' => $canManage || ($liste->make_new_entry && ($preview || ! $buchbarkeit['gesperrt'])),
        ];
    }
}

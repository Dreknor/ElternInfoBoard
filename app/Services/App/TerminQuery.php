<?php

namespace App\Services\App;

use App\Model\listen_termine;
use App\Model\Termin;
use App\Model\User;
use Carbon\Carbon;

/**
 * Termine der Gruppen des Nutzers im Zeitraum, ohne Duplikate, optional mit eigenen Listen-Buchungen (B-30).
 */
class TerminQuery
{
    public static function between(User $user, Carbon $from, Carbon $to, bool $withListen = true): array
    {
        $termine = Termin::query()
            ->where('ende', '>=', $from)
            ->where('start', '<=', $to)
            ->whereExists(function ($q) use ($user) {
                $q->selectRaw(1)->from('group_termine')
                    ->join('group_user', 'group_termine.group_id', '=', 'group_user.group_id')
                    ->whereColumn('group_termine.termin_id', 'termine.id')
                    ->where('group_user.user_id', $user->id);
            })
            ->orderBy('start')
            ->get()
            ->map(fn (Termin $t) => [
                'id' => 'termin-'.$t->id,
                'source' => 'termin',
                'title' => $t->terminname,
                'start' => $t->start->toIso8601String(),
                'end' => $t->ende->toIso8601String(),
                'all_day' => (bool) $t->fullDay,
                'liste_id' => null,
            ]);

        if ($withListen) {
            $booked = listen_termine::query()
                ->with('liste:id,listenname,duration')
                ->whereIn('reserviert_fuer', Family::userIds($user))
                ->whereBetween('termin', [$from, $to])
                ->get()
                ->map(fn (listen_termine $lt) => [
                    'id' => 'liste-'.$lt->id,
                    'source' => 'liste',
                    'title' => $lt->liste?->listenname ?? 'Gebuchter Termin',
                    'start' => $lt->termin->toIso8601String(),
                    'end' => $lt->termin->copy()->addMinutes((int) ($lt->duration ?: $lt->liste?->duration ?: 15))->toIso8601String(),
                    'all_day' => false,
                    'liste_id' => $lt->listen_id,
                ]);
            $termine = $termine->concat($booked)->sortBy('start');
        }

        return $termine->values()->all();
    }

    public static function upcoming(User $user, int $limit): array
    {
        return array_slice(self::between($user, today(), today()->addMonths(6)), 0, $limit);
    }

}

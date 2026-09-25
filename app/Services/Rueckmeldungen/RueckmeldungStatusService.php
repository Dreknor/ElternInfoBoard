<?php

namespace App\Services\Rueckmeldungen;

use App\Model\Post;
use App\Model\UserRueckmeldungen;
use App\Services\Family\FamilyResolver;
use App\Services\Family\FamilyUnit;

/**
 * Rücklauf-Status einer Nachricht mit Rückmeldung: wie viele Antworten
 * werden erwartet, wie viele liegen vor.
 *
 * Im Familien-Scope genügt eine Antwort pro Familie (FamilyResolver).
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §6.5
 */
class RueckmeldungStatusService
{
    public function __construct(private readonly FamilyResolver $resolver) {}

    /**
     * @return array{expected: int, answered: int, open: int, percent: float}
     */
    public function summary(Post $post): array
    {
        $recipients = $post->users->unique('id')->values();
        $units = $this->resolver->familyUnits($recipients);

        $answeredUserIds = UserRueckmeldungen::query()
            ->where('post_id', $post->id)
            ->pluck('users_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        $expected = $units->count();
        $answered = $units->filter(
            fn (FamilyUnit $unit) => array_intersect($unit->userIds, $answeredUserIds) !== []
        )->count();

        return [
            'expected' => $expected,
            'answered' => $answered,
            'open' => max(0, $expected - $answered),
            'percent' => $expected > 0 ? round($answered / $expected * 100, 2) : 0.0,
        ];
    }
}

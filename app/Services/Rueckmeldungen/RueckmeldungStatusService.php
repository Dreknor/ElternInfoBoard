<?php

namespace App\Services\Rueckmeldungen;

use App\Enums\GuardianRight;
use App\Model\Child;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use App\Model\User;
use App\Model\UserRueckmeldungen;
use App\Services\Family\FamilyResolver;
use App\Services\Family\FamilyUnit;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rückmeldungen je Scope (E2/E7):
 *
 * - person: jede empfangende Person antwortet selbst
 * - family: eine Antwort pro Familie
 * - child:  eine Antwort pro betroffenem Kind (Kind in einer Gruppe der
 *           Nachricht); antworten dürfen nur Sorgeberechtigte. Empfänger ohne
 *           Beziehung zu einem betroffenen Kind antworten im Familien-Scope.
 *
 * Solange der Legacy-Resolver aktiv ist, wirkt „child“ wie „family“.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §6.5
 */
class RueckmeldungStatusService
{
    public const SCOPE_PERSON = 'person';

    public const SCOPE_FAMILY = 'family';

    public const SCOPE_CHILD = 'child';

    public function __construct(private readonly FamilyResolver $resolver) {}

    public function effectiveScope(?Rueckmeldungen $rueckmeldung): string
    {
        $scope = $rueckmeldung?->scope ?? self::SCOPE_FAMILY;

        if ($scope === self::SCOPE_CHILD && $this->resolver->mode() === FamilyResolver::MODE_LEGACY) {
            return self::SCOPE_FAMILY;
        }

        return in_array($scope, [self::SCOPE_PERSON, self::SCOPE_FAMILY, self::SCOPE_CHILD], true) ? $scope : self::SCOPE_FAMILY;
    }

    /**
     * Kinder in einer der Gruppen der Nachricht (Klasse, Gruppe, weitere Gruppen).
     *
     * @return EloquentCollection<int, Child>
     */
    public function affectedChildren(Post $post): EloquentCollection
    {
        $groupIds = DB::table('group_post')->where('post_id', $post->id)->pluck('group_id')->all();

        if ($groupIds === []) {
            return new EloquentCollection;
        }

        return Child::query()
            ->where(function ($q) use ($groupIds) {
                $q->whereIn('class_id', $groupIds)
                    ->orWhereIn('group_id', $groupIds)
                    ->orWhereExists(fn ($e) => $e->from('child_group')
                        ->whereColumn('child_group.child_id', 'children.id')
                        ->whereIn('child_group.group_id', $groupIds));
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Antwortziele eines Users für eine Nachricht.
     *
     * @return Collection<int, RueckmeldungTarget>
     */
    public function targetsFor(User $user, Post $post): Collection
    {
        $rueckmeldung = $post->rueckmeldung;
        $scope = $this->effectiveScope($rueckmeldung);
        $answers = UserRueckmeldungen::query()->where('post_id', $post->id)->with(['user', 'answers'])->get();

        if ($scope === self::SCOPE_CHILD) {
            $affectedIds = $this->affectedChildren($post)->modelKeys();
            $myChildren = $this->resolver->childrenQuery($user)
                ->whereIn('children.id', $affectedIds)
                ->with(['class', 'group'])
                ->orderBy('first_name')
                ->get();

            if ($myChildren->isNotEmpty()) {
                return $myChildren->map(fn (Child $child) => new RueckmeldungTarget(
                    scope: self::SCOPE_CHILD,
                    child: $child,
                    answers: $answers->where('child_id', $child->id)->values(),
                    canAnswer: $this->resolver->hasAccessToChild($user, $child, GuardianRight::Custody),
                ))->values();
            }
        }

        // person, family oder Fallback ohne betroffenes Kind
        $ids = $scope === self::SCOPE_PERSON ? [$user->id] : $this->resolver->familyUserIds($user);

        return collect([new RueckmeldungTarget(
            scope: $scope === self::SCOPE_PERSON ? self::SCOPE_PERSON : self::SCOPE_FAMILY,
            child: null,
            answers: $answers->whereNull('child_id')->whereIn('users_id', $ids)->values(),
            canAnswer: true,
        )]);
    }

    /**
     * Prüft, ob $user für ($childId) antworten darf, und liefert das Ziel.
     *
     * @return array{target: ?RueckmeldungTarget, error: ?string, status: int}
     */
    public function resolveTarget(User $user, Post $post, ?int $childId, bool $allowExisting = false): array
    {
        if (! $post->rueckmeldung) {
            return ['target' => null, 'error' => 'Rückmeldung für diesen Beitrag nicht aktiviert', 'status' => 404];
        }

        $targets = $this->targetsFor($user, $post);
        $childTargets = $targets->filter(fn (RueckmeldungTarget $t) => $t->isChild());

        if ($childTargets->isNotEmpty()) {
            if ($childId === null) {
                $answerable = $childTargets->filter(fn (RueckmeldungTarget $t) => $t->canAnswer);
                if ($answerable->count() !== 1) {
                    return ['target' => null, 'error' => $answerable->isEmpty()
                        ? 'Nur Sorgeberechtigte können für dieses Kind antworten.'
                        : 'Bitte wählen Sie aus, für welches Kind Sie antworten (bitte App aktualisieren).', 'status' => $answerable->isEmpty() ? 403 : 422];
                }
                $target = $answerable->first();
            } else {
                $target = $childTargets->first(fn (RueckmeldungTarget $t) => $t->child->id === $childId);
                if ($target === null) {
                    return ['target' => null, 'error' => 'Das Kind ist von dieser Rückmeldung nicht betroffen oder gehört nicht zu Ihnen.', 'status' => 403];
                }
            }

            if (! $target->canAnswer) {
                return ['target' => null, 'error' => 'Nur Sorgeberechtigte können für dieses Kind antworten.', 'status' => 403];
            }
        } else {
            $target = $targets->first();
        }

        if (! $allowExisting && $target->isAnswered() && ! $post->rueckmeldung->multiple) {
            return ['target' => $target, 'error' => 'Rückmeldung wurde bereits abgegeben.', 'status' => 409];
        }

        return ['target' => $target, 'error' => null, 'status' => 200];
    }

    /**
     * Darf $user diese Antwort bearbeiten?
     */
    public function mayEdit(User $user, UserRueckmeldungen $answer): bool
    {
        if ($answer->child_id !== null) {
            $child = Child::withTrashed()->find($answer->child_id);

            return $child !== null && $this->resolver->hasAccessToChild($user, $child, GuardianRight::Custody);
        }

        $scope = $this->effectiveScope($answer->nachricht?->rueckmeldung);

        return $scope === self::SCOPE_PERSON
            ? (int) $answer->users_id === $user->id
            : $this->resolver->isSameFamily($user, (int) $answer->users_id);
    }

    /**
     * @return array{expected: int, answered: int, open: int, percent: float, scope: string, unanswerable: int}
     */
    public function summary(Post $post): array
    {
        $scope = $this->effectiveScope($post->rueckmeldung);
        $recipients = $post->users->unique('id')->values();
        $answers = UserRueckmeldungen::query()->where('post_id', $post->id)->get(['users_id', 'child_id']);

        $expected = 0;
        $answered = 0;
        $unanswerable = 0;

        if ($scope === self::SCOPE_CHILD) {
            $children = $this->affectedChildren($post);
            $answeredChildIds = $answers->pluck('child_id')->filter()->unique()->all();

            foreach ($children as $child) {
                if ($this->resolver->guardiansFor($child, GuardianRight::Custody)->isEmpty()) {
                    $unanswerable++;

                    continue;
                }
                $expected++;
                if (in_array($child->id, $answeredChildIds)) {
                    $answered++;
                }
            }

            $recipients = $this->fallbackRecipients($recipients, $children);
            $answers = $answers->whereNull('child_id');
        }

        if ($scope === self::SCOPE_PERSON) {
            $answeredUserIds = $answers->pluck('users_id')->map(fn ($id) => (int) $id)->all();
            $expected += $recipients->count();
            $answered += $recipients->filter(fn (User $u) => in_array($u->id, $answeredUserIds, true))->count();
        } else {
            $answeredUserIds = $answers->pluck('users_id')->map(fn ($id) => (int) $id)->unique()->all();
            $units = $this->resolver->familyUnits($recipients);
            $expected += $units->count();
            $answered += $units->filter(fn (FamilyUnit $unit) => array_intersect($unit->userIds, $answeredUserIds) !== [])->count();
        }

        return [
            'expected' => $expected,
            'answered' => $answered,
            'open' => max(0, $expected - $answered),
            'percent' => $expected > 0 ? round($answered / $expected * 100, 2) : 0.0,
            'scope' => $scope,
            'unanswerable' => $unanswerable,
        ];
    }

    /**
     * Personen, die noch antworten müssen – für Erinnerungen.
     *
     * @return Collection<int, array{user: User, children: list<string>}>  keyed by user id
     */
    public function openRecipients(Post $post): Collection
    {
        $scope = $this->effectiveScope($post->rueckmeldung);
        $recipients = $post->users->unique('id')->values();
        $answers = UserRueckmeldungen::query()->where('post_id', $post->id)->get(['users_id', 'child_id']);
        $open = collect();

        if ($scope === self::SCOPE_CHILD) {
            $children = $this->affectedChildren($post);
            $answeredChildIds = $answers->pluck('child_id')->filter()->unique()->all();

            foreach ($children as $child) {
                if (in_array($child->id, $answeredChildIds)) {
                    continue;
                }
                foreach ($this->resolver->guardiansFor($child, GuardianRight::Custody) as $guardian) {
                    $entry = $open->get($guardian->id, ['user' => $guardian, 'children' => []]);
                    $entry['children'][] = trim($child->first_name.' '.$child->last_name);
                    $open->put($guardian->id, $entry);
                }
            }

            $recipients = $this->fallbackRecipients($recipients, $children);
            $answers = $answers->whereNull('child_id');
        }

        $answeredUserIds = $answers->pluck('users_id')->map(fn ($id) => (int) $id)->unique()->all();

        foreach ($recipients as $recipient) {
            $ids = $scope === self::SCOPE_PERSON ? [$recipient->id] : $this->resolver->familyUserIds($recipient);
            if (array_intersect($ids, $answeredUserIds) === [] && ! $open->has($recipient->id)) {
                $open->put($recipient->id, ['user' => $recipient, 'children' => []]);
            }
        }

        return $open;
    }

    /**
     * Empfänger ohne jede Beziehung zu einem betroffenen Kind (Fallback-Familien-Scope).
     *
     * @param  Collection<int, User>  $recipients
     * @param  EloquentCollection<int, Child>  $children
     * @return Collection<int, User>
     */
    private function fallbackRecipients(Collection $recipients, EloquentCollection $children): Collection
    {
        if ($children->isEmpty()) {
            return $recipients;
        }

        $childIds = $children->modelKeys();
        $related = $this->resolver->childIdsByUser($recipients);

        return $recipients->filter(fn (User $u) => array_intersect($related[$u->id] ?? [], $childIds) === [])->values();
    }
}

<?php

namespace App\Services;

use App\Enums\GuardianRight;
use App\Model\Post;
use App\Model\ReadReceipts;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use App\Services\Rueckmeldungen\RueckmeldungStatusService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Wann gilt eine Lesebestätigung für eine Person als erledigt?
 *
 * - family (Standard, bisher): eine Bestätigung irgendeines Familienmitglieds genügt
 * - person: jede Person bestätigt selbst
 * - child:  je betroffenem Kind (Kind in den Gruppen der Nachricht) genügt die
 *           Bestätigung irgendeiner Bezugsperson des Kindes – auch aus einer anderen
 *           Familie (getrennt lebende Eltern). Personen ohne betroffenes Kind: wie family.
 *           Im Modus "legacy" des FamilyResolvers wirkt "child" wie "family".
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §6.7
 */
class ReadReceiptStatusService
{
    public const SCOPE_FAMILY = 'family';

    public const SCOPE_PERSON = 'person';

    public const SCOPE_CHILD = 'child';

    /** @var array<int, list<int>> post_id => betroffene Kind-IDs */
    private array $affectedChildIds = [];

    public function __construct(
        private readonly FamilyResolver $resolver,
        private readonly RueckmeldungStatusService $rueckmeldungen,
    ) {}

    public function effectiveScope(Post $post): string
    {
        $scope = $post->read_receipt_scope ?? self::SCOPE_FAMILY;

        if ($scope === self::SCOPE_CHILD && $this->resolver->mode() === FamilyResolver::MODE_LEGACY) {
            return self::SCOPE_FAMILY;
        }

        return in_array($scope, [self::SCOPE_FAMILY, self::SCOPE_PERSON, self::SCOPE_CHILD], true) ? $scope : self::SCOPE_FAMILY;
    }

    /**
     * Bestätigte Lesebestätigungen der Nachricht (user_id => Receipt).
     *
     * @return Collection<int, ReadReceipts>
     */
    public function confirmedReceipts(Post $post): Collection
    {
        $receipts = $post->relationLoaded('receipts') ? $post->receipts : $post->receipts()->get();

        return $receipts->whereNotNull('confirmed_at')->keyBy('user_id')->toBase();
    }

    /**
     * Bestätigung, durch die $user als erledigt gilt – oder null.
     *
     * @param  Collection<int, ReadReceipts>|null  $confirmed  aus confirmedReceipts() (Sammelabfragen)
     */
    public function satisfyingReceipt(User $user, Post $post, ?Collection $confirmed = null): ?ReadReceipts
    {
        $confirmed ??= $this->confirmedReceipts($post);

        if ($own = $confirmed->get($user->id)) {
            return $own;
        }

        return match ($this->effectiveScope($post)) {
            self::SCOPE_PERSON => null,
            self::SCOPE_CHILD => $this->satisfiedByChildGuardians($user, $post, $confirmed),
            default => $this->firstConfirmed($confirmed, $this->resolver->familyUserIds($user)),
        };
    }

    public function isSatisfied(User $user, Post $post, ?Collection $confirmed = null): bool
    {
        return $this->satisfyingReceipt($user, $post, $confirmed) !== null;
    }

    /**
     * IDs der Nachrichten, deren Lesebestätigung für $user noch offen ist.
     *
     * @param  iterable<Post>  $posts
     * @return list<int>
     */
    public function openPostIds(User $user, iterable $posts): array
    {
        $posts = collect($posts)->filter(fn (Post $p) => (bool) $p->read_receipt);
        if ($posts->isEmpty()) {
            return [];
        }

        $confirmedByPost = ReadReceipts::query()
            ->whereIn('post_id', $posts->pluck('id'))
            ->whereNotNull('confirmed_at')
            ->get()
            ->groupBy('post_id');

        return $posts
            ->reject(fn (Post $post) => $this->isSatisfied($user, $post, ($confirmedByPost->get($post->id) ?? collect())->keyBy('user_id')))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Beschreibung für Eltern, was „bestätigt“ bedeutet.
     */
    public function scopeHint(Post $post): string
    {
        return match ($this->effectiveScope($post)) {
            self::SCOPE_PERSON => 'Jede Person bestätigt selbst.',
            self::SCOPE_CHILD => 'Je Kind genügt die Bestätigung einer Bezugsperson.',
            default => 'Die Bestätigung eines Familienmitglieds genügt.',
        };
    }

    private function satisfiedByChildGuardians(User $user, Post $post, Collection $confirmed): ?ReadReceipts
    {
        $affectedIds = $this->affectedChildIds[$post->id] ??= $this->rueckmeldungen->affectedChildren($post)->modelKeys();
        $myChildIds = $affectedIds === [] ? [] : $this->resolver
            ->childrenQuery($user, GuardianRight::Information)
            ->whereIn('children.id', $affectedIds)
            ->pluck('children.id')
            ->all();

        if ($myChildIds === []) {
            return $this->firstConfirmed($confirmed, $this->resolver->familyUserIds($user));
        }

        if ($confirmed->isEmpty()) {
            return null;
        }

        // Je Kind: Bezugspersonen (mit Informationsrecht) mit Bestätigung
        $guardiansByChild = DB::table('child_user')
            ->whereIn('child_id', $myChildIds)
            ->whereIn('user_id', $confirmed->pluck('user_id')->all())
            ->where('receives_information', true)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', today()))
            ->get(['child_id', 'user_id'])
            ->groupBy('child_id');

        $receipt = null;
        foreach ($myChildIds as $childId) {
            $guardianIds = collect($guardiansByChild->get($childId))->pluck('user_id')->map(fn ($id) => (int) $id)->all();
            $childReceipt = $this->firstConfirmed($confirmed, $guardianIds);
            if ($childReceipt === null) {
                return null;
            }
            $receipt ??= $childReceipt;
        }

        return $receipt;
    }

    /**
     * @param  list<int>  $userIds
     */
    private function firstConfirmed(Collection $confirmed, array $userIds): ?ReadReceipts
    {
        // Bewusst nach user_id filtern: Eloquent\Collection::only() filtert nach Model-IDs
        return $confirmed
            ->filter(fn (ReadReceipts $receipt) => in_array((int) $receipt->user_id, $userIds, true))
            ->sortBy('confirmed_at')
            ->first();
    }
}

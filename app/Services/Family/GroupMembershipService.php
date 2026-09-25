<?php

namespace App\Services\Family;

use App\Enums\GuardianRight;
use App\Model\Child;
use App\Model\Conversation;
use App\Model\User;
use Illuminate\Support\Facades\DB;

/**
 * Leitet Gruppenmitgliedschaften von Bezugspersonen aus ihren Kindern ab
 * (Klasse, Gruppe, weitere Gruppen, AG-Gruppen).
 *
 * - Abgeleitete Mitgliedschaften sind group_user-Zeilen mit is_auto_provisioned = true.
 * - Manuelle Mitgliedschaften (Elternrat, Förderverein, Altbestand) bleiben unberührt.
 * - Gruppenkonversationen (Messenger) werden mitgeführt.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §5.4
 */
class GroupMembershipService
{
    public function __construct(private readonly FamilyResolver $resolver) {}

    /**
     * Soll-Gruppen des Users: group_id => child_id (für provisioned_via_child_id).
     *
     * @return array<int, int>
     */
    public function desiredGroups(User $user): array
    {
        $desired = [];

        $children = $this->resolver->childrenFor($user, GuardianRight::Information);
        foreach ($children as $child) {
            foreach ($child->derivedGroupIds() as $groupId) {
                $desired[$groupId] ??= $child->id;
            }
        }

        return $desired;
    }

    /**
     * Gleicht die abgeleiteten Mitgliedschaften eines Users ab.
     *
     * @param  list<int>  $adoptGroupIds  Manuelle Pivots dieser Gruppen werden als
     *                                    abgeleitet übernommen (z. B. AG-Gruppen,
     *                                    die früher manuell gepflegt wurden).
     * @return array{attached: list<int>, detached: list<int>}
     */
    public function syncDerivedGroups(User $user, array $adoptGroupIds = []): array
    {
        $desired = $this->desiredGroups($user);

        if ($adoptGroupIds !== []) {
            DB::table('group_user')
                ->where('user_id', $user->id)
                ->whereIn('group_id', $adoptGroupIds)
                ->where('is_auto_provisioned', false)
                ->update(['is_auto_provisioned' => true]);
        }

        $existing = DB::table('group_user')
            ->where('user_id', $user->id)
            ->get(['group_id', 'is_auto_provisioned'])
            ->mapWithKeys(fn ($row) => [(int) $row->group_id => (bool) $row->is_auto_provisioned]);

        $toDetach = $existing
            ->filter(fn (bool $auto, int $groupId) => $auto && ! array_key_exists($groupId, $desired))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $toAttach = [];
        foreach ($desired as $groupId => $childId) {
            if (! $existing->has($groupId)) {
                $toAttach[] = $groupId;
                DB::table('group_user')->insert([
                    'user_id' => $user->id,
                    'group_id' => $groupId,
                    'is_auto_provisioned' => true,
                    'provisioned_via_child_id' => $childId,
                    'synced_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($existing->get($groupId)) {
                DB::table('group_user')
                    ->where('user_id', $user->id)
                    ->where('group_id', $groupId)
                    ->update(['provisioned_via_child_id' => $childId, 'synced_at' => now()]);
            }
        }

        if ($toDetach !== []) {
            DB::table('group_user')
                ->where('user_id', $user->id)
                ->whereIn('group_id', $toDetach)
                ->where('is_auto_provisioned', true)
                ->delete();
        }

        $this->joinGroupConversations($user, $toAttach);
        $this->leaveGroupConversations($user, $toDetach);

        return ['attached' => $toAttach, 'detached' => $toDetach];
    }

    /**
     * Alle Bezugspersonen eines Kindes abgleichen (z. B. nach Klassenwechsel).
     *
     * @param  list<int>  $adoptGroupIds
     */
    public function syncForChild(Child $child, array $adoptGroupIds = []): void
    {
        foreach ($this->resolver->guardiansFor($child) as $guardian) {
            $this->syncDerivedGroups($guardian, $adoptGroupIds);
        }
    }

    /**
     * @param  iterable<int>  $userIds
     */
    public function syncUsers(iterable $userIds): void
    {
        User::query()->whereIn('id', collect($userIds)->unique()->values())->each(
            fn (User $user) => $this->syncDerivedGroups($user)
        );
    }

    /**
     * User in aktive Gruppenkonversationen der Gruppen aufnehmen.
     *
     * @param  list<int>  $groupIds
     */
    public function joinGroupConversations(User $user, array $groupIds): void
    {
        if ($groupIds === []) {
            return;
        }

        $conversations = Conversation::withoutGlobalScopes()
            ->whereIn('group_id', $groupIds)
            ->where('type', 'group')
            ->where('is_active', true)
            ->get();

        foreach ($conversations as $conversation) {
            $conversation->users()->syncWithoutDetaching([$user->id => ['joined_at' => now()]]);
        }
    }

    /**
     * User aus Gruppenkonversationen der Gruppen austragen, sofern er der Gruppe
     * nicht mehr (manuell oder abgeleitet) angehört.
     *
     * @param  list<int>  $groupIds
     */
    public function leaveGroupConversations(User $user, array $groupIds): void
    {
        if ($groupIds === []) {
            return;
        }

        $stillMember = DB::table('group_user')->where('user_id', $user->id)->whereIn('group_id', $groupIds)->pluck('group_id')->all();
        $leftGroupIds = array_values(array_diff($groupIds, $stillMember));

        if ($leftGroupIds === []) {
            return;
        }

        $conversationIds = Conversation::withoutGlobalScopes()
            ->whereIn('group_id', $leftGroupIds)
            ->where('type', 'group')
            ->pluck('id');

        DB::table('conversation_user')
            ->whereIn('conversation_id', $conversationIds)
            ->where('user_id', $user->id)
            ->delete();
    }
}

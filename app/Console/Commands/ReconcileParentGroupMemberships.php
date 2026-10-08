<?php

namespace App\Console\Commands;

use App\Model\User;
use App\Services\Family\GroupMembershipService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Nach dem ersten vollständigen Kind-Import (§12.4): manuelle Mitgliedschaften
 * in Klassen-/Betreuungsgruppen, die sich jetzt aus Kindern ableiten lassen,
 * werden als abgeleitet übernommen. Verbleibende manuelle Mitgliedschaften in
 * solchen Gruppen ohne passendes Kind werden gemeldet (optional entfernt).
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §12.4
 */
class ReconcileParentGroupMemberships extends Command
{
    protected $signature = 'groups:reconcile-parent-memberships
        {--dry-run : Nur anzeigen, keine Änderungen}
        {--remove-orphans : Manuelle Klassen-Mitgliedschaften ohne passendes Kind entfernen}';

    protected $description = 'Manuelle Eltern-Mitgliedschaften in Klassen-/Betreuungsgruppen mit den Kindern abgleichen.';

    public function handle(GroupMembershipService $groups): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Gruppen, die als Klasse/Gruppe/weitere Gruppe eines Kindes vorkommen
        $childGroupIds = DB::table('children')->whereNull('deleted_at')->pluck('class_id')
            ->merge(DB::table('children')->whereNull('deleted_at')->pluck('group_id'))
            ->merge(DB::table('child_group')->pluck('group_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $manual = DB::table('group_user')
            ->where('is_auto_provisioned', false)
            ->whereIn('group_id', $childGroupIds)
            ->get(['user_id', 'group_id'])
            ->groupBy('user_id');

        $converted = 0;
        $orphans = [];

        foreach ($manual as $userId => $rows) {
            $user = User::find($userId);
            if ($user === null) {
                continue;
            }

            $desired = $groups->desiredGroups($user);

            foreach ($rows as $row) {
                $groupId = (int) $row->group_id;

                if (array_key_exists($groupId, $desired)) {
                    $converted++;
                    if (! $dryRun) {
                        DB::table('group_user')->where('user_id', $userId)->where('group_id', $groupId)->update([
                            'is_auto_provisioned' => true,
                            'provisioned_via_child_id' => $desired[$groupId],
                            'synced_at' => now(),
                        ]);
                    }

                    continue;
                }

                $orphans[] = [$userId, $user->name, $groupId];
            }
        }

        if (! $dryRun && $this->option('remove-orphans')) {
            foreach ($orphans as [$userId, , $groupId]) {
                DB::table('group_user')->where('user_id', $userId)->where('group_id', $groupId)->where('is_auto_provisioned', false)->delete();
                $groups->leaveGroupConversations(User::find($userId), [$groupId]);
            }
        }

        if ($dryRun) {
            $this->info('Dry-Run: keine Änderungen.');
        }
        $this->info("Als abgeleitet übernommen: {$converted}");
        $this->info('Manuelle Klassen-Mitgliedschaften ohne passendes Kind: '.count($orphans)
            .($this->option('remove-orphans') && ! $dryRun ? ' (entfernt)' : ''));

        if ($orphans !== []) {
            $this->table(['User-ID', 'Name', 'Gruppe'], array_slice($orphans, 0, 200));
        }

        return self::SUCCESS;
    }
}

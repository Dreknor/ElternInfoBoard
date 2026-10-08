<?php

namespace App\Console\Commands;

use App\Model\Conversation;
use App\Model\Group;
use App\Model\Post;
use App\Settings\UcsSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Hard-Purge-Job für verwaiste UCS-Klassen-Gruppen.
 *
 * Löscht SoftDeleted Klassen-Gruppen mit ucs_source='kelvin', deren
 * deleted_at älter als UcsSetting::purge_after_days (Default: 14) ist.
 *
 * Kaskaden-Reihenfolge pro Gruppe (innerhalb einer DB-Transaktion):
 *   1. Posts: Detach aus group_post-Pivot; orphaned Posts (nur in dieser Gruppe) → softDelete
 *   2. Conversation: Messages + conversation_user-Pivot löschen, Conversation forceDelete
 *   3. Pivots: group_termine, group_listen, arbeitsgemeinschaften_groups, group_user
 *   4. Group: forceDelete()
 *
 * @see docs/ucs-kelvin-integration-konzept.md §7.3
 */
class UcsPurgeStaleClasses extends Command
{
    protected $signature = 'ucs:purge-stale-classes';

    protected $description = 'Hard-Purge verwaister UCS-Klassen-Gruppen (SoftDeleted + älter als purge_after_days).';

    public function handle(): int
    {
        /** @var UcsSetting $settings */
        $settings = app(UcsSetting::class);

        if (! $settings->enabled) {
            $this->warn('UCS-Integration ist deaktiviert – Purge übersprungen.');

            return self::SUCCESS;
        }

        $days     = max(1, $settings->purge_after_days ?? 14);
        $cutoff   = now()->subDays($days);
        $purged   = 0;
        $errors   = 0;

        $this->info("Suche SoftDeleted Kelvin-Gruppen älter als {$days} Tage (vor ".($cutoff->toDateString()).')…');

        Group::withoutGlobalScopes()
            ->onlyTrashed()
            ->where('ucs_source', 'kelvin')
            ->where('deleted_at', '<', $cutoff)
            ->each(function (Group $group) use (&$purged, &$errors) {
                try {
                    [$postCount, $convCount] = $this->purgeGroup($group);

                    $this->line(
                        "purged group_id={$group->id} \"{$group->name}\""
                        ." (posts={$postCount}, conversations={$convCount})"
                    );

                    Log::channel('ucs')->info('[ucs:purge-stale-classes] Gruppe gepurgt', [
                        'group_id'      => $group->id,
                        'name'          => $group->name,
                        'posts'         => $postCount,
                        'conversations' => $convCount,
                    ]);

                    $purged++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error("Fehler bei group_id={$group->id}: ".$e->getMessage());
                    Log::channel('ucs')->error('[ucs:purge-stale-classes] Fehler', [
                        'group_id' => $group->id,
                        'error'    => $e->getMessage(),
                    ]);
                }
            });

        $this->info("Abgeschlossen: {$purged} Gruppe(n) gepurgt, {$errors} Fehler.");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Führt die vollständige Kaskaden-Löschung einer einzelnen Gruppe aus.
     *
     * @return array{0: int, 1: int}  [post_count, conversation_count]
     */
    private function purgeGroup(Group $group): array
    {
        return DB::transaction(function () use ($group): array {
            $postCount = 0;
            $convCount = 0;

            // ── 1. Posts ──────────────────────────────────────────────────────
            // Alle Posts dieser Gruppe ermitteln
            $postIds = DB::table('group_post')
                ->where('group_id', $group->id)
                ->pluck('post_id')
                ->all();

            if (! empty($postIds)) {
                // Detach aus group_post-Pivot
                DB::table('group_post')->where('group_id', $group->id)->delete();

                foreach ($postIds as $postId) {
                    // Orphaned Post: nur noch in keiner anderen Gruppe → softDelete
                    $stillInOtherGroup = DB::table('group_post')
                        ->where('post_id', $postId)
                        ->exists();

                    if (! $stillInOtherGroup) {
                        Post::withoutGlobalScopes()->where('id', $postId)->delete();
                        $postCount++;
                    }
                }
            }

            // ── 2. Conversation ───────────────────────────────────────────────
            /** @var Conversation|null $conversation */
            $conversation = Conversation::withTrashed()
                ->where('group_id', $group->id)
                ->first();

            if ($conversation !== null) {
                // Nachrichten löschen
                $conversation->messages()->forceDelete();

                // conversation_user-Pivot löschen
                DB::table('conversation_user')
                    ->where('conversation_id', $conversation->id)
                    ->delete();

                // Conversation hard-löschen
                $conversation->forceDelete();
                $convCount++;
            }

            // ── 3. Weitere Pivot-Tabellen ─────────────────────────────────────
            DB::table('group_termine')
                ->where('group_id', $group->id)
                ->delete();

            DB::table('group_listen')
                ->where('group_id', $group->id)
                ->delete();

            DB::table('arbeitsgemeinschaften_groups')
                ->where('group_id', $group->id)
                ->delete();

            // group_user (auto + manuelle Pivots)
            DB::table('group_user')
                ->where('group_id', $group->id)
                ->delete();

            // ── 4. Gruppe hard-löschen ────────────────────────────────────────
            $group->forceDelete();

            return [$postCount, $convCount];
        });
    }
}


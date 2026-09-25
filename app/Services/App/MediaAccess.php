<?php

namespace App\Services\App;

use App\Model\Child;
use App\Model\Discussion;
use App\Model\Group;
use App\Model\Krankmeldungen;
use App\Model\Mail;
use App\Model\Message;
use App\Model\Post;
use App\Model\Site;
use App\Model\SiteBlock;
use App\Model\User;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Wer darf eine Mediendatei abrufen? (B-07)
 * Standard ist „nein“ – nur bekannte Besitzer-Modelle werden freigegeben.
 */
class MediaAccess
{
    public static function canView(User $user, Media $media): bool
    {
        if ($user->can('upload files')) {
            return true;
        }

        $model = $media->model;

        return match (true) {
            $model instanceof Post => $user->can('view', $model),
            $model instanceof Group => self::inGroups($user, [$model->id]),
            $model instanceof Krankmeldungen => in_array((int) $model->users_id, Family::userIds($user), true)
                || $user->can('manage diseases'),
            $model instanceof Message => $model->isVisibleTo($user),
            $model instanceof Child => Family::ownsChild($user, $model) || $user->can('edit schickzeiten'),
            $model instanceof Discussion => $user->can('view elternrat'),
            $model instanceof Mail => (int) $model->senders_id === $user->id || $user->can('see mails'),
            $model instanceof Site => self::inGroups($user, $model->groups()->pluck('groups.id')->all()),
            $model !== null && method_exists($model, 'site') => self::siteBlockVisible($user, $model),
            default => false,
        };
    }

    private static function inGroups(User $user, array $groupIds): bool
    {
        return ! empty($groupIds) && DB::table('group_user')
            ->where('user_id', $user->id)
            ->whereIn('group_id', $groupIds)
            ->exists();
    }

    /** Dateien/Bilder aus CMS-Blöcken: sichtbar, wenn die zugehörige Seite sichtbar ist. */
    private static function siteBlockVisible(User $user, $blockContent): bool
    {
        $block = SiteBlock::where('block_type', get_class($blockContent))->where('block_id', $blockContent->id)->first();
        $site = $block?->site;

        return $site !== null && self::inGroups($user, $site->groups()->pluck('groups.id')->all());
    }
}

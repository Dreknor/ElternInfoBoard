<?php

namespace App\Services\App;

use App\Model\Child;
use App\Model\Discussion;
use App\Model\Group;
use App\Model\Groups;
use App\Model\Krankmeldungen;
use App\Model\Mail;
use App\Model\Message;
use App\Model\Post;
use App\Model\Site;
use App\Model\SiteBlock;
use App\Model\SiteBlockFiles;
use App\Model\SiteBlockImages;
use App\Model\User;
use App\Services\Security\SecurityLog;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Wer darf eine Mediendatei abrufen? (B-07)
 * Standard ist „nein“ – nur bekannte Besitzer-Modelle werden freigegeben.
 */
class MediaAccess
{
    /**
     * Download-Adresse einer Mediendatei. Ältere Einträge (vor der Spalte `uuid`) haben keine UUID –
     * dann über die ID (gleiche Zugriffsprüfung), statt dass `route()` die ganze Antwort abbricht.
     */
    public static function downloadUrl(Media $media): string
    {
        return filled($media->uuid)
            ? route('api.files.download', ['media_uuid' => $media->uuid])
            : url('api/image/'.$media->id);
    }

    public static function canView(User $user, Media $media): bool
    {
        // Beiträge nur für Schüler blendet ParentAudienceScope aus – die Policy entscheidet selbst
        // Mail-Archiv: der globale Scope „own“ blendet fremde Mails aus – ob jemand sie sehen darf (`see mails`),
        // entscheidet die Regel unten, nicht der Scope.
        $model = match ($media->model_type) {
            (new Post)->getMorphClass() => Post::withStudentAudience()->find($media->model_id),
            (new Mail)->getMorphClass() => Mail::withoutGlobalScopes()->find($media->model_id),
            default => $media->model,
        };

        // Anhänge vertraulicher Beiträge: nur Autor und Empfänger, auch nicht mit „upload files“
        if ($model instanceof Post && $model->isHiddenFrom($user)) {
            return false;
        }

        // „upload files“ ist die Dateiverwaltung für Inhalte (Beiträge, Gruppen, Seiten). Sie gilt nur dort –
        // Krankmeldungen, Nachrichten, Mails, Kinder, Diskussionen und unbekannte Besitzer laufen immer
        // durch die modellbezogene Regel.
        if (self::isContentModel($model) && $user->can('upload files')) {
            return true;
        }

        return match (true) {
            $model instanceof Post => $user->can('view', $model),
            $model instanceof Group => self::inGroups($user, [$model->id]),
            $model instanceof Krankmeldungen => in_array((int) $model->users_id, Family::userIds($user), true)
                || ($model->child_id && ($child = \App\Model\Child::find($model->child_id)) && $user->can('viewHealth', $child))
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

    /** Redaktionelle Inhalte (Beitrags-, Gruppen- und Seitendateien), die die Dateiverwaltung betreffen. */
    private static function isContentModel(mixed $model): bool
    {
        return $model instanceof Post
            || $model instanceof Group
            || $model instanceof Groups
            || $model instanceof Site
            || $model instanceof SiteBlock
            || $model instanceof SiteBlockFiles
            || $model instanceof SiteBlockImages;
    }

    /**
     * Sicherheitsprotokoll für Dateiabrufe. Nur schutzwürdige Besitzer (Krankmeldungen, Nachrichten, Mails,
     * Kinder, Diskussionen …) werden erfasst; Beitrags-, Gruppen- und Seitendateien nicht (würde das Protokoll
     * mit Inline-Bildern fluten und ein Nutzungsprofil erzeugen). Kontext: nur IDs und Klassen-Kurzname.
     */
    public static function logDownload(Media $media): void
    {
        try {
            $model = $media->model;
        } catch (\Throwable) {
            $model = null;
        }

        if ($model !== null && self::isContentModel($model)) {
            return;
        }

        SecurityLog::record('media.download', null, [
            'media_id' => $media->id,
            'owner_type' => class_basename($model ?? (string) $media->model_type),
            'owner_id' => $media->model_id,
        ]);
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

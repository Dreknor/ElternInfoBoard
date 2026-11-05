<?php

namespace App\Services\Students;

use App\Model\Post;
use App\Model\Termin;
use App\Model\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Was ein Schülerkonto sieht (docs/konzept-schuelerkonten.md §4, §5.3, Paket S-05).
 *
 * Ersetzt für Schüler PostPolicy, PostQuery und TerminQuery: nur veröffentlichte Einträge mit
 * `zielgruppe` ≠ `eltern`, die an eine Klasse oder Gruppe des eigenen Kindes gehen. Direkte
 * Freigaben an Personen und externe Angebote gelten für Schüler nicht.
 */
class StudentPostQuery
{
    public static function posts(User $student): Builder
    {
        $groupIds = StudentContext::groupIds($student);

        return Post::query()
            ->forStudentAudience()
            ->where('posts.released', 1)
            ->where('posts.external', 0)
            ->where(fn ($q) => $groupIds === []
                ? $q->whereRaw('1 = 0')
                : $q->whereExists(fn ($sub) => $sub->selectRaw(1)
                    ->from('group_post')
                    ->whereColumn('group_post.post_id', 'posts.id')
                    ->whereIn('group_post.group_id', $groupIds)));
    }

    /** Nicht archivierte Beiträge, neueste zuerst. */
    public static function currentPosts(User $student): Builder
    {
        return self::posts($student)
            ->where(fn ($q) => $q->whereNull('posts.archiv_ab')->orWhere('posts.archiv_ab', '>', now()))
            ->orderByDesc('posts.sticky')
            ->orderByDesc('posts.updated_at');
    }

    public static function findPost(User $student, int $id): ?Post
    {
        return self::posts($student)->whereKey($id)->first();
    }

    public static function termine(User $student): Builder
    {
        $groupIds = StudentContext::groupIds($student);

        return Termin::query()
            ->forStudentAudience()
            ->where(fn ($q) => $groupIds === []
                ? $q->whereRaw('1 = 0')
                : $q->whereExists(fn ($sub) => $sub->selectRaw(1)
                    ->from('group_termine')
                    ->whereColumn('group_termine.termin_id', 'termine.id')
                    ->whereIn('group_termine.group_id', $groupIds)))
            ->orderBy('start');
    }

    /** Vertretungen der eigenen Klassen (ab heute), ohne Lehrkräfte-Abwesenheiten. */
    public static function vertretungen(User $student): \Illuminate\Support\Collection
    {
        $groupIds = StudentContext::groupIds($student);
        $child = StudentContext::child($student);
        if ($child === null) {
            return collect();
        }

        $provider = app(\App\Services\StundenplanDataProvider::class);
        $data = $provider->getStundenplanData();
        $kurzformen = $data ? $provider->klassenForChild($child, $data) : [];

        return \App\Model\Vertretung::query()
            ->with('group:id,name')
            ->where(fn ($q) => $q->whereIn('klasse', $groupIds ?: [0])->orWhereIn('klasse_kurzform', $kurzformen ?: ['__none__']))
            ->orderBy('date')->orderBy('stunde')
            ->get()
            ->map(function (\App\Model\Vertretung $v) {
                $v->date = \Carbon\Carbon::parse($v->getRawOriginal('date'))->toDateString();

                return $v;
            });
    }
}

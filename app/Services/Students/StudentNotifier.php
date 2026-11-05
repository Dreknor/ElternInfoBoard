<?php

namespace App\Services\Students;

use App\Model\Child;
use App\Model\Post;
use App\Model\Termin;
use App\Model\User;
use App\Services\Notifications\PushDispatcher;
use App\Support\StudentAccounts;

/**
 * Push an Schülerkonten (docs/konzept-schuelerkonten.md §4, §8).
 *
 * Nur für Einträge mit Zielgruppe `eltern_schueler` oder `schueler`, nur an aktive Schülerkonten
 * der Klassen/Gruppen des Eintrags, nur in die App (Schüler haben kein Browser-Abo) und nur mit
 * dem Titel, ohne Text. E-Mails gehen nie an Schüler.
 */
final class StudentNotifier
{
    public static function postReleased(Post $post): void
    {
        if (! $post->released || $post->external || ! $post->isForStudents() || ! StudentAccounts::enabled()) {
            return;
        }

        $ids = self::studentsInGroups($post->groups()->withoutGlobalScopes()->pluck('groups.id')->all());

        PushDispatcher::dispatch($ids, 'Neue Nachricht', $post->header, route('student.posts.show', $post->id), 'Nachrichten');
    }

    public static function terminCreated(Termin $termin): void
    {
        if (! $termin->isForStudents() || ! StudentAccounts::enabled()) {
            return;
        }

        $ids = self::studentsInGroups($termin->groups()->withoutGlobalScopes()->pluck('groups.id')->all());

        PushDispatcher::dispatch($ids, 'Neuer Termin', $termin->terminname, route('student.termine'), 'Termine');
    }

    /**
     * Aktive Schülerkonten, deren Kind (Klasse, Gruppe, weitere Gruppen) in einer der Gruppen ist.
     *
     * @param  list<int>  $groupIds
     * @return list<int>
     */
    public static function studentsInGroups(array $groupIds): array
    {
        if ($groupIds === []) {
            return [];
        }

        $childIds = Child::query()
            ->whereNotNull('user_id')
            ->where('status', '!=', Child::STATUS_LEFT)
            ->where(fn ($q) => $q->whereIn('class_id', $groupIds)
                ->orWhereIn('group_id', $groupIds)
                ->orWhereExists(fn ($s) => $s->selectRaw(1)->from('child_group')
                    ->whereColumn('child_group.child_id', 'children.id')
                    ->whereIn('child_group.group_id', $groupIds)))
            ->pluck('user_id');

        return User::students()->where('is_active', true)->whereIn('id', $childIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}

<?php

namespace App\Services\Students;

use App\Model\Child;
use App\Model\Group;
use App\Model\User;
use Illuminate\Support\Collection;

/**
 * Wozu gehört ein Schülerkonto? (docs/konzept-schuelerkonten.md §2)
 *
 * Schülerkonten haben keine Gruppenmitgliedschaft. Klassen und Gruppen ergeben sich allein
 * aus dem verknüpften Kind (`children.user_id`): Klasse, Gruppe, weitere Gruppen, AG-Gruppen.
 * Ein Konto ohne Kind oder mit ausgeschiedenem Kind sieht nichts.
 */
class StudentContext
{
    /** @var array<int, array{child: ?Child, groups: list<int>}> */
    private static array $cache = [];

    public static function child(User $student): ?Child
    {
        return self::resolve($student)['child'];
    }

    /** @return list<int> */
    public static function groupIds(User $student): array
    {
        return self::resolve($student)['groups'];
    }

    /** Klassen- und Gruppennamen (für die Anzeige). */
    public static function groupNames(User $student): Collection
    {
        $ids = self::groupIds($student);

        return $ids === [] ? collect() : Group::withoutGlobalScopes()->whereIn('id', $ids)->orderBy('name')->pluck('name');
    }

    /** Anzeigename der Klasse (Klasse vor Gruppe). */
    public static function className(User $student): ?string
    {
        $child = self::child($student);
        if ($child === null) {
            return null;
        }

        $id = $child->class_id ?: $child->group_id;

        return $id ? Group::withoutGlobalScopes()->whereKey($id)->value('name') : null;
    }

    public static function forget(?User $student = null): void
    {
        if ($student === null) {
            self::$cache = [];
        } else {
            unset(self::$cache[$student->id]);
        }
    }

    /** @return array{child: ?Child, groups: list<int>} */
    private static function resolve(User $student): array
    {
        if (! $student->isStudent()) {
            return ['child' => null, 'groups' => []];
        }

        if (isset(self::$cache[$student->id]) && ! app()->runningInConsole()) {
            return self::$cache[$student->id];
        }

        $child = Child::query()->where('user_id', $student->id)->first();

        if ($child === null || $child->status === Child::STATUS_LEFT) {
            return self::$cache[$student->id] = ['child' => null, 'groups' => []];
        }

        return self::$cache[$student->id] = ['child' => $child, 'groups' => $child->derivedGroupIds()];
    }
}

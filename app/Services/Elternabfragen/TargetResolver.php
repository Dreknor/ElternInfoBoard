<?php

namespace App\Services\Elternabfragen;

use App\Enums\GuardianRight;
use App\Model\Child;
use App\Model\Inquiry;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Zielgruppe einer Abfrage: betroffene Kinder (Klassen, Jahrgang als Gruppe,
 * mehrere Gruppen oder gesamte Schule) und die je Kind berechtigten Konten
 * (Sorgerecht = Berechtigung zur Abgabe von Erklärungen, Konzept Kap. 7.1).
 */
class TargetResolver
{
    public function __construct(private readonly FamilyResolver $family) {}

    public function childrenQuery(Inquiry $inquiry): Builder
    {
        $query = Child::query()->where(fn ($q) => $q->whereNull('status')->orWhere('status', Child::STATUS_ACTIVE));

        if ($inquiry->target_type === Inquiry::TARGET_ALL) {
            return $query;
        }

        $groupIds = array_map('intval', $inquiry->target_group_ids ?? []);
        if ($groupIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($groupIds) {
            $q->whereIn('class_id', $groupIds)
                ->orWhereIn('group_id', $groupIds)
                ->orWhereIn('id', DB::table('child_group')->whereIn('group_id', $groupIds)->select('child_id'));
        });
    }

    /** @return Collection<int, Child> */
    public function children(Inquiry $inquiry): Collection
    {
        return $this->childrenQuery($inquiry)->orderBy('last_name')->orderBy('first_name')->get();
    }

    /** @return Collection<int, User> */
    public function recipients(Child $child): Collection
    {
        return $this->family->guardiansFor($child, GuardianRight::Custody)->unique('id')->values();
    }

    /**
     * Erwartete Anzahl betroffener Kinder und Elternkonten vor dem Start (Prozess B).
     *
     * @return array{children: int, accounts: int, without_accounts: list<string>, without_email: list<string>, by_group: array<string, int>}
     */
    public function preview(Inquiry $inquiry): array
    {
        $children = $this->childrenQuery($inquiry)->with(['class', 'group'])->orderBy('last_name')->get();
        $accounts = [];
        $withoutAccounts = [];
        $withoutEmail = [];
        $byGroup = [];

        foreach ($children as $child) {
            $recipients = $this->recipients($child);
            $name = trim($child->first_name.' '.$child->last_name);
            if ($recipients->isEmpty()) {
                $withoutAccounts[] = $name;
            }
            foreach ($recipients as $user) {
                $accounts[$user->id] = true;
                if (blank($user->email)) {
                    $withoutEmail[$user->id] = $user->name;
                }
            }
            $groupName = $child->class?->name ?? $child->group?->name ?? 'ohne Klasse';
            $byGroup[$groupName] = ($byGroup[$groupName] ?? 0) + 1;
        }
        ksort($byGroup, SORT_NATURAL);

        return [
            'children' => $children->count(),
            'accounts' => count($accounts),
            'without_accounts' => $withoutAccounts,
            'without_email' => array_values($withoutEmail),
            'by_group' => $byGroup,
        ];
    }
}

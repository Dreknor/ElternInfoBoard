<?php

namespace App\Console\Commands;

use App\Model\Child;
use App\Model\User;
use App\Services\Family\FamilyBuilder;
use Illuminate\Console\Command;

/**
 * Listet Klärungsfälle der Familienbildung: Personen, die über gemeinsame
 * Kinder verbunden sind, aber nicht eindeutig eine Familie bilden (Patchwork).
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §7.2
 */
class FamilyReview extends Command
{
    protected $signature = 'family:review';

    protected $description = 'Klärungsfälle der automatischen Familienbildung anzeigen.';

    public function handle(FamilyBuilder $builder): int
    {
        $cases = $builder->reviewCases();

        if ($cases === []) {
            $this->info('Keine Klärungsfälle.');

            return self::SUCCESS;
        }

        foreach ($cases as $index => $case) {
            $this->line('');
            $this->info('Klärungsfall '.($index + 1));
            $users = User::query()->whereIn('id', $case['userIds'])->get()->keyBy('id');
            $children = Child::query()->whereIn('id', $case['childIds'])->get()->keyBy('id');

            $rows = [];
            foreach ($case['childSets'] as $userId => $childIds) {
                $rows[] = [
                    $userId,
                    $users->get($userId)?->name,
                    $users->get($userId)?->family?->name ?? '–',
                    collect($childIds)->map(fn ($id) => $children->get($id)?->first_name.' '.$children->get($id)?->last_name)->implode(', '),
                ];
            }
            $this->table(['User-ID', 'Name', 'Familie', 'Kinder'], $rows);
        }

        return self::SUCCESS;
    }
}

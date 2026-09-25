<?php

namespace Tests\Feature\Performance;

use App\Model\Group;
use App\Model\Pflichtstunde;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use App\Services\Family\FamilyResolver;
use App\Services\Pflichtstunden\PflichtstundenService;
use App\Services\Rueckmeldungen\RueckmeldungStatusService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * FAM-13.2 Performance: Die Anzahl der Datenbankabfragen darf nicht mit der
 * Zahl der Familien wachsen (kein N+1 wie früher bei User::find($user->sorg2)).
 *
 * @group performance
 */
class FamilyModelQueryCountTest extends TestCase
{
    use BuildsFamilies;

    public static function modes(): array
    {
        return [
            'legacy' => [FamilyResolver::MODE_LEGACY],
            'child_centric' => [FamilyResolver::MODE_CHILD_CENTRIC],
        ];
    }

    private function seedFamilies(int $count, ?Group $class = null): void
    {
        for ($i = 0; $i < $count; $i++) {
            [$a, $b] = $this->coupleWithSharedChild(['class_id' => ($class ?? Group::factory()->create())->id]);
            $a->givePermissionTo('view Pflichtstunden');
            $b->givePermissionTo('view Pflichtstunden');
            $start = now()->subDays(2)->setTime(9, 0);
            Pflichtstunde::factory()->create(['user_id' => $a->id, 'start' => $start, 'end' => $start->copy()->addHour(), 'approved' => true]);
            if ($class) {
                $a->groups()->syncWithoutDetaching([$class->id]);
                $b->groups()->syncWithoutDetaching([$class->id]);
            }
        }
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    #[Test]
    #[DataProvider('modes')]
    public function pflichtstunden_overview_query_count_is_constant(string $mode): void
    {
        Permission::findOrCreate('view Pflichtstunden', 'web');
        $this->useResolver($mode);

        $this->seedFamilies(5);
        app(PflichtstundenService::class)->overview(); // Aufwärmen (Permission-Cache)
        $small = $this->countQueries(fn () => app(PflichtstundenService::class)->overview());

        $this->seedFamilies(25);
        $large = $this->countQueries(fn () => app(PflichtstundenService::class)->overview());

        $this->assertSame($small, $large, "Pflichtstunden-Übersicht ({$mode}): {$small} vs. {$large} Abfragen");
        $this->assertLessThan(20, $large);
    }

    public static function scopes(): array
    {
        return ['family' => ['family'], 'child' => ['child']];
    }

    #[Test]
    #[DataProvider('scopes')]
    public function rueckmeldung_summary_query_count_does_not_grow_per_family(string $scope): void
    {
        Permission::findOrCreate('view Pflichtstunden', 'web');
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);
        $class = Group::factory()->create(['protected' => false]);
        $post = Post::factory()->create(['released' => 1, 'archiv_ab' => now()->addDays(5)]);
        $post->groups()->attach($class->id);
        Rueckmeldungen::factory()->create(['post_id' => $post->id, 'scope' => $scope, 'type' => 'email']);

        $this->seedFamilies(5, $class);
        app(RueckmeldungStatusService::class)->summary($post->fresh()); // Aufwärmen
        $small = $this->countQueries(fn () => app(RueckmeldungStatusService::class)->summary($post->fresh()));

        $this->seedFamilies(25, $class);
        $large = $this->countQueries(fn () => app(RueckmeldungStatusService::class)->summary($post->fresh()));

        $this->assertSame($small, $large, "Rücklauf-Status: {$small} vs. {$large} Abfragen");
    }
}

<?php

namespace Tests\Feature;

use App\Model\Group;
use App\Model\Holiday;
use App\Model\Reinigung;
use App\Model\ReinigungsTask;
use App\Model\User;
use App\Services\HolidayService;
use App\Settings\ReinigungSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\ActivatesModules;
use Tests\TestCase;

/**
 * Ferienwochen im Reinigungsplan (ReinigungSetting::$skip_holidays).
 *
 * Testdaten: Weihnachtsferien Mi 23.12.2026 – Sa 02.01.2027, gespeichert unter
 * dem Jahr 2026 (wie von der Ferien-API geliefert).
 *  - Woche ab 21.12.: 3 Schultage Ferien  → Ferienwoche
 *  - Woche ab 28.12.: komplett Ferien     → Ferienwoche
 *  - Woche ab 04.01.: keine Ferien
 * Dazu Ferien Mo 15.02. – Di 16.02.2027 (nur 2 Schultage) → keine Ferienwoche.
 */
class ReinigungFerienTest extends TestCase
{
    use ActivatesModules, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activateModule('Reinigung');

        // Keine Anfragen an die Ferien-API im Test.
        $this->app->bind(HolidayService::class, fn () => new class('SN') extends HolidayService
        {
            protected function fetchAndStoreHolidays(int $year): Collection
            {
                return collect();
            }
        });

        Holiday::create([
            'year' => 2026, 'bundesland' => 'SN', 'name' => 'weihnachtsferien',
            'start' => '2026-12-23', 'end' => '2027-01-02',
        ]);
        Holiday::create([
            'year' => 2027, 'bundesland' => 'SN', 'name' => 'kurzferien',
            'start' => '2027-02-15', 'end' => '2027-02-16',
        ]);

        $this->settings(true);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function settings(bool $skipHolidays): void
    {
        (new ReinigungSetting)->fill([
            'separate_bereiche' => true,
            'combined_exclude_bereiche' => [],
            'skip_holidays' => $skipHolidays,
            'reminder_enabled' => false,
            'reminder_days_before' => 3,
            'reminder_email' => true,
            'reminder_push' => true,
            'reminder_time' => '08:00',
        ])->save();
    }

    private function editReinigungUser(): User
    {
        $user = User::factory()->create(['changePassword' => false]);
        Permission::findOrCreate('edit reinigung');
        $user->givePermissionTo('edit reinigung');

        return $user;
    }

    /** @test */
    public function a_week_counts_as_holiday_week_when_most_school_days_are_holidays(): void
    {
        $service = app(HolidayService::class);

        $this->assertSame('weihnachtsferien', $service->holidayForWeek(Carbon::parse('2026-12-21'))?->name);
        $this->assertSame('weihnachtsferien', $service->holidayForWeek(Carbon::parse('2026-12-28'))?->name);
        $this->assertNull($service->holidayForWeek(Carbon::parse('2027-01-04')));
        $this->assertNull($service->holidayForWeek(Carbon::parse('2027-02-15')));
    }

    /** @test */
    public function holidays_reaching_into_january_are_found_via_the_previous_year(): void
    {
        $service = app(HolidayService::class);

        $this->assertTrue($service->isHoliday(Carbon::parse('2027-01-01')));
        $this->assertFalse($service->isHoliday(Carbon::parse('2027-01-04')));
    }

    /** @test */
    public function autocreate_skips_holiday_weeks(): void
    {
        $user = $this->editReinigungUser();
        $group = Group::factory()->create(['bereich' => 'Kindergarten']);
        User::factory()->create()->groups()->attach($group);
        $task = ReinigungsTask::factory()->create();

        $this->actingAs($user)->post('reinigung/Kindergarten/auto', [
            'aufgaben' => [$task->id],
            'exclude' => [],
            'start' => '2026-12-14',
            'end' => '2027-01-10',
        ])->assertSessionHas('type', 'success');

        $wochen = Reinigung::query()->get()
            ->map(fn ($r) => Carbon::parse($r->getRawOriginal('datum'))->toDateString())
            ->sort()->values()->all();

        $this->assertSame(['2026-12-14', '2027-01-04'], $wochen);
    }

    /** @test */
    public function autocreate_plans_holiday_weeks_when_setting_is_off(): void
    {
        $this->settings(false);

        $user = $this->editReinigungUser();
        $group = Group::factory()->create(['bereich' => 'Kindergarten']);
        User::factory()->create()->groups()->attach($group);
        $task = ReinigungsTask::factory()->create();

        $this->actingAs($user)->post('reinigung/Kindergarten/auto', [
            'aufgaben' => [$task->id],
            'exclude' => [],
            'start' => '2026-12-14',
            'end' => '2027-01-10',
        ])->assertSessionHas('type', 'success');

        $this->assertSame(4, Reinigung::query()->count());
    }

    /** @test */
    public function plan_marks_holiday_weeks_without_cleaning(): void
    {
        Carbon::setTestNow('2026-12-01 10:00:00');

        $user = $this->editReinigungUser();
        Group::factory()->create(['bereich' => 'Kindergarten']);

        $response = $this->actingAs($user)->get('reinigung');

        $response->assertOk();
        $response->assertViewHas('ferienWochen', fn ($wochen) => array_keys($wochen) === ['2026-12-21', '2026-12-28']);
        $response->assertSee('Weihnachtsferien – keine Reinigung');
    }

    /** @test */
    public function plan_marks_holiday_weeks_also_when_setting_is_off(): void
    {
        $this->settings(false);
        Carbon::setTestNow('2026-12-01 10:00:00');

        $user = $this->editReinigungUser();
        Group::factory()->create(['bereich' => 'Kindergarten']);

        $response = $this->actingAs($user)->get('reinigung');

        $response->assertOk();
        $response->assertViewHas('ferienWochen', fn ($wochen) => array_keys($wochen) === ['2026-12-21', '2026-12-28']);
        $response->assertViewHas('ferienAuslassen', false);
        $response->assertSee('Ferien: Weihnachtsferien');
        $response->assertDontSee('keine Reinigung');
    }
}

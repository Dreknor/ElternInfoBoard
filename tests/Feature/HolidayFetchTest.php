<?php

namespace Tests\Feature;

use App\Model\Holiday;
use App\Services\HolidayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Ferienabruf: ferien-api.de, ersatzweise openholidaysapi.org; Fehler werden
 * eine Stunde lang nicht wiederholt.
 */
class HolidayFetchTest extends TestCase
{
    use RefreshDatabase;

    private const FERIEN_API = [
        ['start' => '2026-10-26T00:00Z', 'end' => '2026-10-30T00:00Z', 'year' => 2026, 'stateCode' => 'BW', 'name' => 'herbstferien'],
        ['start' => '2026-12-23T00:00Z', 'end' => '2027-01-09T00:00Z', 'year' => 2026, 'stateCode' => 'BW', 'name' => 'weihnachtsferien'],
    ];

    private const OPEN_HOLIDAYS = [
        // Weihnachten des Vorjahres reicht ins Jahr – gehört zu 2025 und wird für 2026 nicht gespeichert
        ['startDate' => '2025-12-22', 'endDate' => '2026-01-05', 'name' => [['language' => 'DE', 'text' => 'Weihnachtsferien']]],
        ['startDate' => '2026-10-26', 'endDate' => '2026-10-30', 'name' => [['language' => 'DE', 'text' => 'Herbstferien']]],
        ['startDate' => '2026-12-23', 'endDate' => '2027-01-09', 'name' => [['language' => 'EN', 'text' => 'Christmas'], ['language' => 'DE', 'text' => 'Weihnachtsferien']]],
    ];

    private function service(): HolidayService
    {
        return new HolidayService('BW');
    }

    /** @test */
    public function loads_from_ferien_api_with_user_agent(): void
    {
        Http::fake(['ferien-api.de/*' => Http::response(self::FERIEN_API)]);

        $holidays = $this->service()->getHolidaysForYear(2026);

        $this->assertCount(2, $holidays);
        $this->assertTrue($this->service()->isHoliday(Carbon::parse('2027-01-08')));
        Http::assertSent(fn ($request) => str_contains($request->header('User-Agent')[0] ?? '', 'ElternInfoBoard'));
    }

    /** @test */
    public function falls_back_to_openholidays_when_ferien_api_fails(): void
    {
        Http::fake([
            'ferien-api.de/*' => Http::response('Forbidden', 403),
            'openholidaysapi.org/*' => Http::response(self::OPEN_HOLIDAYS),
        ]);

        $holidays = $this->service()->getHolidaysForYear(2026);

        $this->assertSame(['Herbstferien', 'Weihnachtsferien'], $holidays->sortBy('start')->pluck('name')->values()->all());
        $this->assertSame('2026-12-23', $holidays->firstWhere('name', 'Weihnachtsferien')->start->toDateString());
        Http::assertSent(fn ($request) => str_contains($request->url(), 'subdivisionCode=DE-BW'));
    }

    /** @test */
    public function failure_is_not_retried_for_an_hour(): void
    {
        Http::fake(['*' => Http::response('down', 503)]);

        $this->assertTrue($this->service()->getHolidaysForYear(2026)->isEmpty());
        $this->assertTrue($this->service()->getHolidaysForYear(2026)->isEmpty());

        Http::assertSentCount(2); // ein Versuch je Quelle, kein zweiter Durchlauf
        $this->assertTrue(Cache::has('ferien_fetch_failed_BW_2026'));
    }

    /** @test */
    public function refresh_keeps_existing_holidays_when_sources_are_down(): void
    {
        Holiday::create(['year' => 2026, 'bundesland' => 'BW', 'name' => 'herbstferien', 'start' => '2026-10-26', 'end' => '2026-10-30']);
        Http::fake(['*' => Http::response('down', 503)]);

        $this->assertSame(0, $this->service()->refresh(2026));
        $this->assertSame(1, Holiday::where('year', 2026)->where('bundesland', 'BW')->count());
    }

    /** @test */
    public function artisan_command_reports_loaded_holidays(): void
    {
        Http::fake(['ferien-api.de/*' => Http::response(self::FERIEN_API)]);
        (new \App\Settings\CareSetting)->fill(['bundesland' => 'BW'])->save();

        $this->artisan('holidays:refresh', ['year' => 2026])
            ->expectsOutputToContain('2026: 2 Ferienzeiträume geladen.')
            ->assertSuccessful();
    }
}

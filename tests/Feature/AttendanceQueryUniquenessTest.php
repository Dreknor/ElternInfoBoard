<?php

namespace Tests\Feature;

use App\Http\Controllers\Anwesenheit\CareController;
use App\Http\Middleware\PasswordExpired;
use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Je Kind darf pro Tag nur eine Anwesenheitsabfrage bestehen – Kinder müssen aber
 * nachträglich zu einer bestehenden Abfrage hinzugefügt werden können.
 */
class AttendanceQueryUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Child $childA;

    private Child $childB;

    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        Permission::findOrCreate('edit schickzeiten', 'web');
        Permission::findOrCreate('manage attendance queries', 'web');

        $this->admin = User::factory()->create(['password_changed_at' => now()]);
        $this->admin->givePermissionTo(['edit schickzeiten', 'manage attendance queries']);

        $this->childA = Child::factory()->create();
        $this->childB = Child::factory()->create();

        $this->monday = Carbon::parse('next monday');
    }

    private function storeAbfrage(array $childIds, array $overrides = [], ?User $user = null)
    {
        return $this->actingAs($user ?? $this->admin)
            ->withoutMiddleware(PasswordExpired::class)
            ->post(route('care.abfrage.store'), array_merge([
                'date_start' => $this->monday->toDateString(),
                'date_end' => $this->monday->copy()->addDays(4)->toDateString(),
                'target_type' => 'children',
                'target_ids' => $childIds,
                'should_be' => '',
            ], $overrides));
    }

    #[Test]
    public function creating_the_same_query_twice_does_not_create_duplicates(): void
    {
        $this->storeAbfrage([$this->childA->id]);
        $this->storeAbfrage([$this->childA->id]);

        $this->assertSame(5, ChildCheckIn::where('child_id', $this->childA->id)->count());
    }

    #[Test]
    public function children_can_be_added_later_without_touching_existing_answers(): void
    {
        $this->storeAbfrage([$this->childA->id]);

        // Eltern von Kind A haben für Montag bereits geantwortet
        $answered = ChildCheckIn::where('child_id', $this->childA->id)->orderBy('date')->first();
        $answered->update(['should_be' => false]);

        $this->storeAbfrage([$this->childA->id, $this->childB->id])
            ->assertSessionHas('Meldung', fn ($m) => str_contains($m, '5 Abfrage(n) neu erstellt'));

        $this->assertSame(5, ChildCheckIn::where('child_id', $this->childA->id)->count());
        $this->assertSame(5, ChildCheckIn::where('child_id', $this->childB->id)->count());
        $this->assertFalse($answered->fresh()->should_be);
    }

    #[Test]
    public function explicit_should_be_updates_existing_entries(): void
    {
        $this->storeAbfrage([$this->childA->id]);
        $this->storeAbfrage([$this->childA->id], ['should_be' => '1']);

        $this->assertSame(5, ChildCheckIn::where('child_id', $this->childA->id)->where('should_be', true)->count());
    }

    #[Test]
    public function creating_queries_requires_the_dedicated_permission(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        $user->givePermissionTo('edit schickzeiten');

        $this->storeAbfrage([$this->childA->id], [], $user)->assertForbidden();

        $this->actingAs($user)
            ->withoutMiddleware(PasswordExpired::class)
            ->delete(route('care.abfrage.destroy', ['date' => $this->monday->toDateString()]))
            ->assertForbidden();

        $this->assertSame(0, ChildCheckIn::count());
    }

    #[Test]
    public function database_rejects_a_second_check_in_for_the_same_child_and_day(): void
    {
        $row = ['child_id' => $this->childA->id, 'date' => $this->monday->toDateString(), 'checked_in' => false, 'checked_out' => false];
        DB::table('child_check_ins')->insert($row);

        $this->expectException(QueryException::class);
        DB::table('child_check_ins')->insert($row);
    }

    #[Test]
    public function daily_check_in_reuses_an_existing_query_entry(): void
    {
        $this->travelTo($this->monday->copy()->setTime(8, 0));
        $this->childA->update(['auto_checkIn' => true]);

        DB::table('child_check_ins')->insert([
            'child_id' => $this->childA->id,
            'date' => today()->toDateString(),
            'checked_in' => false,
            'checked_out' => false,
            'should_be' => null,
        ]);

        $settings = new \App\Settings\CareSetting;
        $settings->auto_checkin_enabled_schulzeit = true;
        $settings->auto_checkin_enabled_ferien = true;
        $settings->save();

        app(CareController::class)->dailyCheckIn();
        app(CareController::class)->dailyCheckIn();

        $checkIns = ChildCheckIn::where('child_id', $this->childA->id)->get();
        $this->assertCount(1, $checkIns);
        $this->assertTrue($checkIns->first()->checked_in);
    }

    #[Test]
    public function migration_merges_existing_duplicates(): void
    {
        $migration = require database_path('migrations/2026_10_05_000001_add_unique_child_date_to_child_check_ins_table.php');
        $migration->down();

        $date = $this->monday->toDateString();
        $base = ['child_id' => $this->childA->id, 'date' => $date, 'checked_in' => false, 'checked_out' => false];
        DB::table('child_check_ins')->insert($base + ['should_be' => null, 'updated_at' => '2026-09-24 10:00:00']);
        DB::table('child_check_ins')->insert($base + ['should_be' => true, 'updated_at' => '2026-09-25 10:00:00']);
        $latestId = DB::table('child_check_ins')->insertGetId($base + ['should_be' => null, 'comment' => 'Kommt später', 'updated_at' => '2026-09-30 10:00:00']);

        // Widersprüchliche Rückmeldungen: die zuletzt geänderte gewinnt
        $conflict = ['child_id' => $this->childB->id, 'date' => $date, 'checked_in' => false, 'checked_out' => false];
        DB::table('child_check_ins')->insert($conflict + ['should_be' => true, 'created_at' => '2026-09-24 10:00:00', 'updated_at' => '2026-10-01 18:00:00']);
        DB::table('child_check_ins')->insert($conflict + ['should_be' => false, 'created_at' => '2026-09-28 10:00:00', 'updated_at' => '2026-09-29 08:00:00']);

        $migration->up();

        $this->assertSame(1, (int) DB::table('child_check_ins')->where('child_id', $this->childB->id)->value('should_be'));
        $this->assertSame(1, DB::table('child_check_ins')->where('child_id', $this->childB->id)->count());

        $remaining = DB::table('child_check_ins')->where('child_id', $this->childA->id)->get();
        $this->assertCount(1, $remaining);
        // Zuletzt geänderter Eintrag bleibt, leere Rückmeldung wird aus dem Duplikat ergänzt
        $this->assertSame($latestId, (int) $remaining->first()->id);
        $this->assertSame('Kommt später', $remaining->first()->comment);
        $this->assertSame(1, (int) $remaining->first()->should_be);
        $this->assertTrue(Schema::hasIndex('child_check_ins', ['child_id', 'date'], 'unique'));
    }
}

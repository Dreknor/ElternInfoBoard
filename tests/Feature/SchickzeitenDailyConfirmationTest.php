<?php

namespace Tests\Feature;

use App\Model\Child;
use App\Model\Schickzeiten;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-Tests für die Bestätigung bei Änderung/Löschung von Schickzeiten
 */
class SchickzeitenDailyConfirmationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function when_changing_regular_schickzeit_with_daily_times_user_gets_confirmation(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create();
        $child->parents()->attach($user);

        // Erstelle regelmäßige Schickzeit für Montag
        $regularSchickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
            'child_id' => $child->id,
            'weekday' => 1, // Montag
            'specific_date' => null,
            'type' => 'genau',
            'time' => '14:00',
        ]);

        // Erstelle tagesaktuelle Schickzeit für nächsten Montag
        $nextMonday = Carbon::now()->next(1);
        $dailySchickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
            'child_id' => $child->id,
            'weekday' => null,
            'specific_date' => $nextMonday,
            'type' => 'genau',
            'time' => '15:00',
        ]);

        // Versuche die regelmäßige Schickzeit zu ändern
        $response = $this->actingAs($user)->post(route('schickzeiten.store', [
            'child' => $child->id,
            'weekday' => 'Montag',
        ]), [
            'type' => 'genau',
            'time' => '16:00',
        ]);

        // Erwarte Bestätigungsdialog
        $response->assertSessionHas('type', 'confirm');
        $response->assertSessionHas('Meldung');
    }

    /**
     * @test
     */
    public function user_can_update_daily_times_when_changing_regular_schickzeit(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create();
        $child->parents()->attach($user);

        // Erstelle regelmäßige Schickzeit für Montag
        $regularSchickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
            'child_id' => $child->id,
            'weekday' => 1,
            'specific_date' => null,
            'type' => 'genau',
            'time' => '14:00',
        ]);

        // Erstelle tagesaktuelle Schickzeit für nächsten Montag
        $nextMonday = Carbon::now()->next(1);
        $dailySchickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
            'child_id' => $child->id,
            'weekday' => null,
            'specific_date' => $nextMonday,
            'type' => 'genau',
            'time' => '15:00',
        ]);

        // Ändere die regelmäßige Schickzeit und aktualisiere auch die tagesaktuellen
        $response = $this->actingAs($user)->post(route('schickzeiten.store', [
            'child' => $child->id,
            'weekday' => 'Montag',
        ]), [
            'type' => 'genau',
            'time' => '16:00',
            'update_daily_times' => 'yes',
        ]);

        // Prüfe ob die tagesaktuelle Schickzeit aktualisiert wurde
        $this->assertDatabaseHas('schickzeiten', [
            'id' => $dailySchickzeit->id,
            'time' => '16:00',
        ]);
    }

    /**
     * @test
     */
    public function user_can_delete_daily_times_when_changing_regular_schickzeit(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create();
        $child->parents()->attach($user);

        // Erstelle regelmäßige Schickzeit für Montag
        $regularSchickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
            'child_id' => $child->id,
            'weekday' => 1,
            'specific_date' => null,
            'type' => 'genau',
            'time' => '14:00',
        ]);

        // Erstelle tagesaktuelle Schickzeit für nächsten Montag
        $nextMonday = Carbon::now()->next(1);
        $dailySchickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
            'child_id' => $child->id,
            'weekday' => null,
            'specific_date' => $nextMonday,
            'type' => 'genau',
            'time' => '15:00',
        ]);

        // Ändere die regelmäßige Schickzeit und lösche die tagesaktuellen
        $response = $this->actingAs($user)->post(route('schickzeiten.store', [
            'child' => $child->id,
            'weekday' => 'Montag',
        ]), [
            'type' => 'genau',
            'time' => '16:00',
            'update_daily_times' => 'delete',
        ]);

        // Prüfe ob die tagesaktuelle Schickzeit gelöscht wurde
        $this->assertSoftDeleted('schickzeiten', [
            'id' => $dailySchickzeit->id,
        ]);
    }

    /**
     * @test
     */
    public function when_deleting_regular_schickzeit_with_daily_times_user_gets_confirmation(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create();
        $child->parents()->attach($user);

        // Erstelle regelmäßige Schickzeit für Montag
        $regularSchickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
            'child_id' => $child->id,
            'weekday' => 1,
            'specific_date' => null,
            'type' => 'genau',
            'time' => '14:00',
        ]);

        // Erstelle tagesaktuelle Schickzeit für nächsten Montag
        $nextMonday = Carbon::now()->next(1);
        $dailySchickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
            'child_id' => $child->id,
            'weekday' => null,
            'specific_date' => $nextMonday,
            'type' => 'genau',
            'time' => '15:00',
        ]);

        // Versuche die regelmäßige Schickzeit zu löschen
        $response = $this->actingAs($user)->delete(route('schickzeiten.destroy', [
            'schickzeit' => $regularSchickzeit->id,
        ]));

        // Erwarte Bestätigungsdialog
        $response->assertSessionHas('type', 'confirm_delete_schickzeit');
        $response->assertSessionHas('Meldung');
    }

    /**
     * @test
     */
    public function user_can_keep_daily_times_when_deleting_regular_schickzeit(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create();
        $child->parents()->attach($user);

        // Erstelle regelmäßige Schickzeit für Montag
        $regularSchickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
            'child_id' => $child->id,
            'weekday' => 1,
            'specific_date' => null,
            'type' => 'genau',
            'time' => '14:00',
        ]);

        // Erstelle tagesaktuelle Schickzeit für nächsten Montag
        $nextMonday = Carbon::now()->next(1);
        $dailySchickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
            'child_id' => $child->id,
            'weekday' => null,
            'specific_date' => $nextMonday,
            'type' => 'genau',
            'time' => '15:00',
        ]);

        // Lösche die regelmäßige Schickzeit, behalte aber die tagesaktuellen
        $response = $this->actingAs($user)->delete(route('schickzeiten.destroy', [
            'schickzeit' => $regularSchickzeit->id,
        ]), [
            'delete_daily_times' => 'no',
        ]);

        // Prüfe ob die regelmäßige Schickzeit gelöscht wurde
        $this->assertSoftDeleted('schickzeiten', [
            'id' => $regularSchickzeit->id,
        ]);

        // Prüfe ob die tagesaktuelle Schickzeit noch existiert
        $this->assertDatabaseHas('schickzeiten', [
            'id' => $dailySchickzeit->id,
        ]);

        $this->assertNull(Schickzeiten::find($dailySchickzeit->id)->deleted_at);
    }

    /**
     * @test
     */
    public function user_can_delete_daily_times_when_deleting_regular_schickzeit(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create();
        $child->parents()->attach($user);

        // Erstelle regelmäßige Schickzeit für Montag
        $regularSchickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
            'child_id' => $child->id,
            'weekday' => 1,
            'specific_date' => null,
            'type' => 'genau',
            'time' => '14:00',
        ]);

        // Erstelle tagesaktuelle Schickzeit für nächsten Montag
        $nextMonday = Carbon::now()->next(1);
        $dailySchickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
            'child_id' => $child->id,
            'weekday' => null,
            'specific_date' => $nextMonday,
            'type' => 'genau',
            'time' => '15:00',
        ]);

        // Lösche die regelmäßige Schickzeit und auch die tagesaktuellen
        $response = $this->actingAs($user)->delete(route('schickzeiten.destroy', [
            'schickzeit' => $regularSchickzeit->id,
        ]), [
            'delete_daily_times' => 'yes',
        ]);

        // Prüfe ob beide Schickzeiten gelöscht wurden
        $this->assertSoftDeleted('schickzeiten', [
            'id' => $regularSchickzeit->id,
        ]);

        $this->assertSoftDeleted('schickzeiten', [
            'id' => $dailySchickzeit->id,
        ]);
    }
}

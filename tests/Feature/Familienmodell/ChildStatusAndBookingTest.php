<?php

namespace Tests\Feature\Familienmodell;

use App\Model\Child;
use App\Model\Group;
use App\Model\Liste;
use App\Model\listen_termine;
use App\Services\Family\FamilyResolver;
use App\Services\Import\SchuelerImportService;
use App\Services\Pflichtstunden\PflichtstundenService;
use App\Services\SchoolYearService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * FAM-16: Kinder-Status (Bewerber/aktiv/abgegangen) und Terminbuchung je Kind.
 */
class ChildStatusAndBookingTest extends TestCase
{
    use BuildsFamilies;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);
    }

    private function termin(array $attributes = [], array $listAttributes = []): listen_termine
    {
        return listen_termine::factory()->create($attributes + [
            'listen_id' => Liste::factory()->create($listAttributes + ['besitzer' => $this->makeParent()->id, 'multiple' => 0])->id,
            'reserviert_fuer' => null,
            'termin' => now()->addDays(3),
        ]);
    }

    #[Test]
    public function new_children_are_active_by_default(): void
    {
        $child = Child::factory()->create();

        $this->assertSame(Child::STATUS_ACTIVE, $child->fresh()->status);
    }

    #[Test]
    public function import_marks_leavers_as_left_and_new_children_as_active(): void
    {
        Role::findOrCreate('Eltern', 'web');
        Group::factory()->create(['name' => '1a', 'protected' => false]);
        $leaver = Child::factory()->create(['external_id' => 'S-OLD']);

        app(SchuelerImportService::class)->run([[
            'schueler_id' => 'S-1', 'kind_vorname' => 'Max', 'kind_nachname' => 'Muster', 'klasse' => '1a',
            'b1_vorname' => 'Erika', 'b1_nachname' => 'Muster', 'b1_e_mail' => 'erika@example.com', 'b1_beziehung' => 'Mutter', 'b1_sorgerecht' => 'J',
        ]], false, true);

        $new = Child::where('external_id', 'S-1')->firstOrFail();
        $this->assertSame(Child::STATUS_ACTIVE, $new->status);
        $this->assertNotNull($new->entry_date);

        $leaver = Child::withTrashed()->find($leaver->id);
        $this->assertSame(Child::STATUS_LEFT, $leaver->status);
        $this->assertTrue($leaver->exit_date->isToday());
        $this->assertSoftDeleted('children', ['id' => $leaver->id]);
    }

    #[Test]
    public function school_year_change_marks_children_without_class_as_left(): void
    {
        $this->actingAs($this->makeParent());
        $leaver = $this->childFor([$this->makeParent()], ['class_id' => null, 'group_id' => null]);

        app(SchoolYearService::class)->runSchoolYearChange([], []);

        $leaver = Child::withTrashed()->find($leaver->id);
        $this->assertSame(Child::STATUS_LEFT, $leaver->status);
        $this->assertSoftDeleted('children', ['id' => $leaver->id]);
    }

    #[Test]
    public function only_active_children_count_for_pflichtstunden(): void
    {
        $settings = app(\App\Settings\PflichtstundenSetting::class);
        $settings->pflichtstunden_basis = 'child';
        $settings->save();

        $parent = $this->makeParent();
        $class = Group::factory()->create(['protected' => false]);
        $this->childFor([$parent], ['class_id' => $class->id]);
        $this->childFor([$parent], ['class_id' => $class->id, 'status' => Child::STATUS_APPLICANT]);

        $unit = app(PflichtstundenService::class)->unitFor($parent);

        $this->assertCount(1, $unit->childIds);
    }

    #[Test]
    public function booked_termin_is_not_overwritten(): void
    {
        $first = $this->makeParent();
        $termin = $this->termin(['reserviert_fuer' => $first->id]);

        $this->actingAs($this->makeParent())->put("listen/termine/{$termin->id}");

        $this->assertSame($first->id, $termin->fresh()->reserviert_fuer);
    }

    #[Test]
    public function parents_book_one_termin_per_child(): void
    {
        $parent = $this->makeParent();
        $max = $this->childFor([$parent]);
        $mia = $this->childFor([$parent]);
        $first = $this->termin();
        $second = $this->termin(['listen_id' => $first->listen_id]);
        $third = $this->termin(['listen_id' => $first->listen_id]);

        $this->actingAs($parent)->put("listen/termine/{$first->id}", ['child_id' => $max->id]);
        $this->actingAs($parent)->put("listen/termine/{$second->id}", ['child_id' => $max->id]);
        $this->actingAs($parent)->put("listen/termine/{$third->id}", ['child_id' => $mia->id]);

        $this->assertSame($max->id, $first->fresh()->child_id);
        $this->assertNull($second->fresh()->reserviert_fuer);
        $this->assertSame($mia->id, $third->fresh()->child_id);
        $this->assertSame($parent->id, $third->fresh()->reserviert_fuer);
    }

    #[Test]
    public function foreign_child_cannot_be_booked(): void
    {
        $foreignChild = $this->childFor([$this->makeParent()]);
        $termin = $this->termin();

        $this->actingAs($this->makeParent())->put("listen/termine/{$termin->id}", ['child_id' => $foreignChild->id]);

        $this->assertNull($termin->fresh()->reserviert_fuer);
    }

    #[Test]
    public function terminliste_offers_only_children_without_booking(): void
    {
        $klasse = Group::factory()->create(['protected' => false, 'owner_id' => null]);
        $parent = $this->makeParent();
        $parent->groups()->attach($klasse);
        $max = $this->childFor([$parent], ['class_id' => $klasse->id, 'group_id' => null, 'first_name' => 'Maximilian']);
        $mia = $this->childFor([$parent], ['class_id' => $klasse->id, 'group_id' => null, 'first_name' => 'Mia-Sophie']);
        $lena = $this->childFor([$parent], ['class_id' => $klasse->id, 'group_id' => null, 'first_name' => 'Lena-Marie']);

        $liste = Liste::factory()->create(['type' => 'termin', 'active' => 1, 'multiple' => 0, 'besitzer' => $this->makeParent()->id, 'ende' => now()->addMonth()]);
        $this->termin(['listen_id' => $liste->id, 'reserviert_fuer' => $parent->id, 'child_id' => $max->id]);
        $this->termin(['listen_id' => $liste->id]);

        $post = \App\Model\Post::factory()->create(['released' => 1, 'author' => $this->makeParent()->id, 'archiv_ab' => now()->addWeek()]);
        $post->groups()->attach($klasse);
        \App\Model\Rueckmeldungen::create([
            'post_id' => $post->id, 'type' => 'terminliste', 'liste_id' => $liste->id, 'scope' => 'child',
            'terminliste_start_date' => today(), 'terminliste_end_date' => today()->addMonth(),
            'ende' => now()->addWeek(), 'text' => 'Elterngespräch', 'empfaenger' => 'schule@example.com', 'pflicht' => 0,
        ]);

        $this->actingAs($parent);
        $html = view('nachrichten.footer.terminliste', ['nachricht' => $post->fresh()])->render();

        $this->assertStringContainsString('name="child_id"', $html);
        $this->assertStringContainsString('für Maximilian', $html);
        $this->assertStringContainsString('<option value="'.$mia->id.'">Mia-Sophie</option>', $html);
        $this->assertStringContainsString('<option value="'.$lena->id.'">Lena-Marie</option>', $html);
        $this->assertStringNotContainsString('<option value="'.$max->id.'">', $html);
    }

    #[Test]
    public function cancellation_resets_child(): void
    {
        $parent = $this->makeParent();
        $child = $this->childFor([$parent]);
        $termin = $this->termin(['reserviert_fuer' => $parent->id, 'child_id' => $child->id]);

        $this->actingAs($parent)->delete("listen/termine/absagen/{$termin->id}", ['text' => 'krank']);

        $this->assertNull($termin->fresh()->reserviert_fuer);
        $this->assertNull($termin->fresh()->child_id);
    }
}

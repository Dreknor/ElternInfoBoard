<?php

namespace Tests\Feature\Familienmodell;

use App\Imports\UsersImport;
use App\Mail\NewUserPasswordMail;
use App\Model\Child;
use App\Model\Family;
use App\Model\Group;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use App\Services\Import\SchuelerImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * FAM-12: Kind-zentrierter Import mit Schüler-ID (E4).
 */
class SchuelerImportTest extends TestCase
{
    use BuildsFamilies;

    private Group $klasse1a;

    private Group $hort;

    private Group $elternrat;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);
        Role::findOrCreate('Eltern', 'web');
        Role::findOrCreate('Aufnahme', 'web');
        $this->klasse1a = Group::factory()->create(['name' => '1a', 'protected' => false]);
        $this->hort = Group::factory()->create(['name' => 'Hort Sonnenschein', 'protected' => false]);
        $this->elternrat = Group::factory()->create(['name' => 'Elternrat', 'protected' => true]);
        Group::factory()->create(['name' => 'Klassenstufe 1', 'protected' => false]);
    }

    private function row(array $overrides = []): array
    {
        return $overrides + [
            'schueler_id' => 'S-1',
            'kind_vorname' => 'Max',
            'kind_nachname' => 'Muster',
            'klassenstufe' => '1',
            'klasse' => '1a',
            'gruppe' => 'Hort Sonnenschein',
            'weitere_gruppen' => 'Elternrat',
            'b1_vorname' => 'Erika', 'b1_nachname' => 'Muster', 'b1_e_mail' => 'erika@example.com', 'b1_beziehung' => 'Mutter', 'b1_sorgerecht' => 'J',
            'b2_vorname' => 'Hans', 'b2_nachname' => 'Muster', 'b2_e_mail' => 'hans@example.com', 'b2_beziehung' => 'Vater', 'b2_sorgerecht' => 'J',
            'b3_vorname' => 'Gisela', 'b3_nachname' => 'Muster', 'b3_e_mail' => 'oma@example.com', 'b3_beziehung' => 'Oma', 'b3_sorgerecht' => '',
        ];
    }

    private function import(array $rows, bool $dryRun = false, bool $leavers = false)
    {
        return app(SchuelerImportService::class)->run($rows, $dryRun, $leavers);
    }

    #[Test]
    public function imports_child_guardians_family_and_derived_groups(): void
    {
        $report = $this->import([$this->row()]);

        $child = Child::where('external_id', 'S-1')->firstOrFail();
        $this->assertSame([$this->klasse1a->id, $this->hort->id], [$child->class_id, $child->group_id]);
        $this->assertSame(1, $report->childrenCreated);
        $this->assertSame(3, $report->usersCreated);
        $this->assertSame(3, $report->linksCreated);
        Mail::assertQueued(NewUserPasswordMail::class, 3);

        $mother = User::where('email', 'erika@example.com')->first();
        $oma = User::where('email', 'oma@example.com')->first();
        $this->assertSame('mother', $child->parents()->where('users.id', $mother->id)->first()->pivot->relation);
        $this->assertFalse($child->parents()->where('users.id', $oma->id)->first()->pivot->has_custody);
        $this->assertSame('import', $child->parents()->where('users.id', $mother->id)->first()->pivot->source);
        $this->assertTrue($mother->hasRole('Eltern'));

        // Familie aus allen drei Bezugspersonen
        $this->assertNotNull($mother->family_id);
        $this->assertSame(3, Family::find($mother->family_id)->users()->count());

        // abgeleitete Gruppen (Klasse, Hort, Klassenstufe) + manuelle Elternrat-Gruppe
        $auto = DB::table('group_user')->where('user_id', $mother->id)->where('is_auto_provisioned', true)->pluck('group_id')->sort()->values()->all();
        $this->assertCount(3, $auto);
        $this->assertTrue(DB::table('group_user')->where('user_id', $mother->id)->where('group_id', $this->elternrat->id)->where('is_auto_provisioned', false)->exists());
    }

    #[Test]
    public function row_without_student_id_is_rejected(): void
    {
        $report = $this->import([$this->row(['schueler_id' => ''])]);

        $this->assertCount(1, $report->errors);
        $this->assertSame(0, Child::count());
    }

    #[Test]
    public function reimport_is_idempotent(): void
    {
        $this->import([$this->row()]);
        $report = $this->import([$this->row()]);

        $this->assertSame(0, $report->childrenCreated);
        $this->assertSame(0, $report->usersCreated);
        $this->assertSame(0, $report->linksCreated);
        $this->assertSame(0, $report->familiesCreated);
        $this->assertSame(1, Child::count());
        $this->assertSame(1, Family::count());
        $this->assertSame(3, DB::table('child_user')->count());
    }

    #[Test]
    public function first_import_matches_existing_child_by_name_and_class(): void
    {
        $existing = Child::factory()->create(['first_name' => 'Max', 'last_name' => 'Muster', 'class_id' => $this->klasse1a->id]);

        $report = $this->import([$this->row()]);

        $this->assertSame(1, $report->childrenMatched);
        $this->assertSame('S-1', $existing->fresh()->external_id);
        $this->assertSame(1, Child::count());
    }

    #[Test]
    public function ambiguous_existing_children_are_reported_not_guessed(): void
    {
        Child::factory()->count(2)->create(['first_name' => 'Max', 'last_name' => 'Muster', 'class_id' => $this->klasse1a->id]);

        $report = $this->import([$this->row()]);

        $this->assertCount(1, $report->review);
        $this->assertSame(0, Child::whereNotNull('external_id')->count());
    }

    #[Test]
    public function ucs_child_is_linked_but_not_overwritten(): void
    {
        $ucs = Child::factory()->create([
            'first_name' => 'Maximilian', 'last_name' => 'Muster', 'external_id' => 'S-1',
            'ucs_source' => 'kelvin', 'ucs_username' => 'max.muster', 'ucs_school' => 'GS', 'ucs_uuid' => 'uuid-1',
        ]);

        $this->import([$this->row()]);

        $this->assertSame('Maximilian', $ucs->fresh()->first_name);
        $this->assertSame(3, $ucs->parents()->count());
    }

    #[Test]
    public function dry_run_writes_nothing_and_sends_no_mail(): void
    {
        $report = $this->import([$this->row()], dryRun: true);

        $this->assertSame(1, $report->childrenCreated);
        $this->assertSame(0, Child::count());
        $this->assertSame(0, User::where('email', 'erika@example.com')->count());
        Mail::assertNothingQueued();
    }

    #[Test]
    public function leavers_are_only_marked_when_requested(): void
    {
        $leaver = Child::factory()->create(['external_id' => 'S-OLD']);

        $preview = $this->import([$this->row()], dryRun: true, leavers: true);
        $this->assertCount(1, $preview->leavers);
        $this->assertNotSoftDeleted('children', ['id' => $leaver->id]);

        $this->import([$this->row()]);
        $this->assertNotSoftDeleted('children', ['id' => $leaver->id]);

        $this->import([$this->row()], leavers: true);
        $this->assertSoftDeleted('children', ['id' => $leaver->id]);
    }

    #[Test]
    public function guardians_of_different_families_are_reported(): void
    {
        $mother = $this->makeParent(['email' => 'erika@example.com']);
        $father = $this->makeParent(['email' => 'hans@example.com']);
        $this->familyOf($mother);
        $this->familyOf($father);

        $report = $this->import([$this->row(['b3_e_mail' => ''])]);

        $this->assertCount(1, $report->review);
        $this->assertNotSame($mother->fresh()->family_id, $father->fresh()->family_id);
    }

    #[Test]
    public function locked_family_is_not_extended(): void
    {
        $mother = $this->makeParent(['email' => 'erika@example.com']);
        $family = Family::factory()->locked()->create();
        $mother->update(['family_id' => $family->id]);

        $report = $this->import([$this->row()]);

        $this->assertCount(1, $report->review);
        $this->assertSame(1, $family->users()->count());
    }

    #[Test]
    public function http_flow_previews_then_imports(): void
    {
        Storage::fake('local');
        Permission::findOrCreate('import user', 'web');
        $admin = $this->makeParent();
        $admin->givePermissionTo('import user');

        $export = new class([$this->templateHeadings(), $this->templateRow()]) implements FromArray
        {
            public function __construct(private array $rows) {}

            public function array(): array
            {
                return $this->rows;
            }
        };
        Excel::store($export, 'upload.xlsx', 'local');
        $file = new UploadedFile(Storage::disk('local')->path('upload.xlsx'), 'schueler.xlsx', null, null, true);

        $preview = $this->actingAs($admin)->post('users/import', ['type' => 'schueler', 'file' => $file])
            ->assertOk()
            ->assertSee('Vorschau')
            ->assertSee('Import bestätigen');
        $this->assertSame(0, Child::count());

        $token = $preview->viewData('token');
        $this->actingAs($admin)->post(route('users.import.schueler.confirm'), ['token' => $token])->assertOk()->assertSee('Ergebnis');

        $this->assertSame(1, Child::where('external_id', 'S-1')->count());
        $this->assertFalse(Storage::disk('local')->exists('imports/'.$token));
    }

    #[Test]
    public function legacy_parent_import_creates_family_instead_of_sorg2(): void
    {
        $header = ['klassenstufe' => 0, 'lerngruppe' => 1, 'S1Vorname' => 2, 'S1Nachname' => 3, 'S1Email' => 4,
            'S2Vorname' => 5, 'S2Nachname' => 6, 'S2Email' => 7, 'gruppen' => 8];

        (new UsersImport($header))->collection(collect([collect(['1', 'x1a', 'Anna', 'Alt', 'anna@example.com', 'Bert', 'Alt', 'bert@example.com', ''])]));
        set_time_limit(0); // UsersImport setzt set_time_limit(20) – würde sonst den Testlauf beenden

        $anna = User::where('email', 'anna@example.com')->first();
        $bert = User::where('email', 'bert@example.com')->first();
        $this->assertNotNull($anna->family_id);
        $this->assertSame($anna->family_id, $bert->family_id);
        $this->assertSame($bert->id, $anna->sorg2, 'Dual-Write für Rollback');
    }

    private function templateHeadings(): array
    {
        return (new \App\Exports\SchuelerImportVorlage)->headings();
    }

    private function templateRow(): array
    {
        return ['S-1', 'Max', 'Muster', '1', '1a', 'Hort Sonnenschein', 'Elternrat',
            'Erika', 'Muster', 'erika@example.com', 'Mutter', 'J',
            'Hans', 'Muster', 'hans@example.com', 'Vater', 'J',
            '', '', '', '', ''];
    }
}

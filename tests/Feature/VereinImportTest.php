<?php

namespace Tests\Feature;

use App\Mail\NewUserPasswordMail;
use App\Model\Group;
use App\Model\User;
use App\Services\Import\VereinImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VereinImportTest extends TestCase
{
    private Group $verein;

    private Group $klasse;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        // Die Settings-Migration legt mail_password unverschlüsselt an; für den Mail-Pfad
        // muss es wie im Betrieb verschlüsselt vorliegen.
        DB::table('settings')->where('group', 'email')->where('name', 'mail_password')
            ->update(['payload' => json_encode(Crypt::encrypt(null))]);
        Role::firstOrCreate(['name' => 'Vereinsmitglied', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Eltern', 'guard_name' => 'web']);
        $this->verein = Group::create(['name' => 'Vereinsmitglied', 'protected' => 1, 'bereich' => 'Verein']);
        $this->klasse = Group::create(['name' => '3a', 'protected' => 0]);
    }

    private function user(string $name, string $email, array $attributes = []): User
    {
        return User::factory()->create($attributes + ['name' => $name, 'email' => $email, 'changePassword' => false]);
    }

    private function row(string $name, ?string $email): array
    {
        return ['person_vorname_nachname' => $name, 'person_e_mail_privat' => $email];
    }

    private function import(array $rows, bool $dryRun = false, bool $removeMissing = true, ?array $selection = null, array $decisions = [])
    {
        return app(VereinImportService::class)->run($rows, dryRun: $dryRun, removeMissing: $removeMissing, selection: $selection, decisions: $decisions);
    }

    private function isMember(User $user): bool
    {
        $user = $user->fresh();

        return $user->groups()->whereKey($this->verein->id)->exists() && $user->hasRole('Vereinsmitglied');
    }

    /** @test */
    public function bestehendes_konto_wird_ueber_email_ohne_beachtung_der_schreibweise_verknuepft(): void
    {
        $parent = $this->user('Anna Beispiel', 'anna@example.com');
        $parent->assignRole('Eltern');
        $parent->groups()->attach($this->klasse->id);

        $report = $this->import([$this->row('Anna Beispiel', ' Anna@Example.COM ')]);

        $this->assertTrue($this->isMember($parent));
        $this->assertSame(1, User::where('email', 'like', '%anna%')->count());
        $this->assertSame([], $report->created);
        // Andere Gruppen und Rollen bleiben erhalten
        $this->assertTrue($parent->fresh()->hasRole('Eltern'));
        $this->assertTrue($parent->fresh()->groups()->whereKey($this->klasse->id)->exists());
        Mail::assertNothingQueued();
    }

    /** @test */
    public function bei_mehreren_adressen_wird_das_konto_der_zweiten_adresse_gefunden(): void
    {
        $user = $this->user('Thomas Mitglied', 't.mitglied@work.example.com');

        $report = $this->import([$this->row('Thomas Mitglied', 'thomas@example.com; t.mitglied@work.example.com')]);

        $this->assertTrue($this->isMember($user));
        $this->assertNull(User::where('email', 'thomas@example.com')->first());
        $this->assertSame([], $report->unassigned);
    }

    /** @test */
    public function nicht_mehr_enthaltene_mitglieder_verlieren_nur_vereinsgruppe_und_rolle(): void
    {
        $alt = $this->user('Alt Mitglied', 'alt@example.com');
        $alt->assignRole(['Vereinsmitglied', 'Eltern']);
        $alt->groups()->attach([$this->verein->id, $this->klasse->id]);

        $report = $this->import([]);

        $alt = $alt->fresh();
        $this->assertFalse($alt->hasRole('Vereinsmitglied'));
        $this->assertFalse($alt->groups()->whereKey($this->verein->id)->exists());
        $this->assertTrue($alt->hasRole('Eltern'));
        $this->assertTrue($alt->groups()->whereKey($this->klasse->id)->exists());
        $this->assertCount(1, $report->removed);
    }

    /** @test */
    public function ohne_abgleich_bleiben_bisherige_mitglieder_erhalten(): void
    {
        $alt = $this->user('Alt Mitglied', 'alt@example.com');
        $alt->assignRole('Vereinsmitglied');
        $alt->groups()->attach($this->verein->id);

        $report = $this->import([], removeMissing: false);

        $this->assertTrue($this->isMember($alt));
        $this->assertSame(1, $report->kept);
    }

    /** @test */
    public function fehlende_rolle_oder_gruppe_wird_bei_bestehenden_mitgliedern_ergaenzt(): void
    {
        $user = $this->user('Nur Gruppe', 'gruppe@example.com');
        $user->groups()->attach($this->verein->id);

        $report = $this->import([$this->row('Nur Gruppe', 'gruppe@example.com')]);

        $this->assertTrue($this->isMember($user));
        $this->assertCount(1, $report->completed);
        $this->assertSame([], $report->removed);
    }

    /** @test */
    public function adressen_verschiedener_konten_werden_nicht_zugeordnet(): void
    {
        $a = $this->user('Person A', 'a@example.com');
        $b = $this->user('Person B', 'b@example.com');

        $report = $this->import([$this->row('Person A', 'a@example.com;b@example.com')]);

        $this->assertFalse($this->isMember($a));
        $this->assertFalse($this->isMember($b));
        $this->assertCount(1, $report->unassigned);
    }

    /** @test */
    public function gleichnamiges_konto_mit_anderer_email_erzeugt_kein_duplikat(): void
    {
        $this->user('Maria Muster', 'maria.alt@example.com');

        $report = $this->import([$this->row('Maria Muster', 'maria.neu@example.com')]);

        $this->assertNull(User::where('email', 'maria.neu@example.com')->first());
        $this->assertSame(1, User::where('name', 'Maria Muster')->count());
        $this->assertCount(1, $report->unassigned);
        Mail::assertNothingQueued();
    }

    /** @test */
    public function konto_im_papierkorb_wird_gemeldet_statt_neu_angelegt(): void
    {
        $this->user('Geloescht', 'weg@example.com')->delete();

        $report = $this->import([$this->row('Geloescht', 'weg@example.com')]);

        $this->assertSame(1, User::withTrashed()->where('email', 'weg@example.com')->count());
        $this->assertCount(1, $report->unassigned);
    }

    /** @test */
    public function lokales_und_ucs_konto_mit_gleicher_email_gelten_als_ein_konto(): void
    {
        $local = $this->user('Doppelt', 'doppelt@example.com');
        $ucs = $this->user('Doppelt', 'doppelt@example.com', ['ucs_source' => 'kelvin']);

        $report = $this->import([$this->row('Doppelt', 'doppelt@example.com')]);

        $this->assertTrue($this->isMember($local));
        $this->assertFalse($this->isMember($ucs));
        $this->assertSame([], $report->unassigned);
    }

    /** @test */
    public function neues_konto_wird_nur_im_echtlauf_angelegt(): void
    {
        $preview = $this->import([$this->row('Neu Mitglied', 'neu@example.com')], dryRun: true);

        $this->assertCount(1, $preview->created);
        $this->assertNull(User::where('email', 'neu@example.com')->first());
        Mail::assertNothingQueued();

        $this->import([$this->row('Neu Mitglied', 'neu@example.com')]);

        $user = User::where('email', 'neu@example.com')->firstOrFail();
        $this->assertTrue($this->isMember($user));
        $this->assertSame(['Vereinsmitglied'], $user->getRoleNames()->all());
        $this->assertSame([$this->verein->id], $user->groups()->pluck('groups.id')->all());
        Mail::assertQueued(NewUserPasswordMail::class, function (NewUserPasswordMail $mail) {
            $html = $mail->render();

            return $mail->notice === VereinImportService::ACCESS_NOTICE
                && str_contains($html, 'Einladungen zur Mitgliederversammlung')
                && str_contains($html, 'Aktuelles aus dem Schulzentrum');
        });
    }

    /** @test */
    public function nur_ausgewaehlte_aenderungen_werden_uebernommen(): void
    {
        $anna = $this->user('Anna Beispiel', 'anna@example.com');
        $bernd = $this->user('Bernd Kurz', 'bernd@example.com');
        $alt = $this->user('Alt Mitglied', 'alt@example.com');
        $alt->assignRole('Vereinsmitglied');
        $alt->groups()->attach($this->verein->id);
        $weg = $this->user('Weg Mitglied', 'weg@example.com');
        $weg->assignRole('Vereinsmitglied');
        $weg->groups()->attach($this->verein->id);

        $rows = [
            $this->row('Anna Beispiel', 'anna@example.com'),
            $this->row('Bernd Kurz', 'bernd@example.com'),
            $this->row('Neu Eins', 'neu1@example.com'),
            $this->row('Neu Zwei', 'neu2@example.com'),
        ];

        $preview = $this->import($rows, dryRun: true);
        $this->assertEqualsCanonicalizing(['user:'.$anna->id, 'user:'.$bernd->id], array_column($preview->linked, 'key'));
        $this->assertEqualsCanonicalizing(['new:neu1@example.com', 'new:neu2@example.com'], array_column($preview->created, 'key'));
        $this->assertEqualsCanonicalizing(['remove:'.$alt->id, 'remove:'.$weg->id], array_column($preview->removed, 'key'));

        $report = $this->import($rows, selection: ['user:'.$anna->id, 'new:neu1@example.com', 'remove:'.$weg->id]);

        $this->assertTrue($this->isMember($anna));
        $this->assertFalse($this->isMember($bernd));
        $this->assertNotNull(User::where('email', 'neu1@example.com')->first());
        $this->assertNull(User::where('email', 'neu2@example.com')->first());
        $this->assertTrue($this->isMember($alt), 'Abgewählte Entfernung lässt das Mitglied bestehen');
        $this->assertFalse($this->isMember($weg));
        $this->assertEqualsCanonicalizing(['Bernd Kurz', 'Neu Zwei'], array_column($report->unassigned, 'name'));
        $this->assertSame(3, $report->membersAfter);
    }

    /** @test */
    public function klaerungsfall_kann_mit_bestehendem_konto_verknuepft_werden(): void
    {
        $maria = $this->user('Maria Muster', 'maria.alt@example.com');
        $maria->assignRole('Eltern');
        $rows = [$this->row('Maria Muster', 'maria.neu@example.com')];

        $preview = $this->import($rows, dryRun: true);
        $this->assertCount(1, $preview->cases);
        $this->assertContains('link:'.$maria->id, array_column($preview->cases[0]['options'], 'value'));
        $this->assertContains('create', array_column($preview->cases[0]['options'], 'value'));

        $report = $this->import($rows, decisions: [2 => 'link:'.$maria->id]);

        $this->assertTrue($this->isMember($maria));
        $this->assertTrue($maria->fresh()->hasRole('Eltern'));
        $this->assertNull(User::where('email', 'maria.neu@example.com')->first());
        $this->assertSame([], $report->unassigned);
        $this->assertCount(1, $report->resolved);
    }

    /** @test */
    public function klaerungsfall_akzeptiert_nur_kandidaten_des_falls(): void
    {
        $this->user('Maria Muster', 'maria.alt@example.com');
        $fremd = $this->user('Fremd', 'fremd@example.com');

        $report = $this->import([$this->row('Maria Muster', 'maria.neu@example.com')], decisions: [2 => 'link:'.$fremd->id]);

        $this->assertFalse($this->isMember($fremd));
        $this->assertCount(1, $report->unassigned);
    }

    /** @test */
    public function klaerungsfall_neues_konto_wiederherstellen_und_per_email(): void
    {
        $this->user('Maria Muster', 'maria.alt@example.com');
        $geloescht = $this->user('Geloescht', 'weg@example.com');
        $geloescht->delete();
        $ziel = $this->user('Hanna Ohne', 'hanna.konto@example.com');

        $report = $this->import([
            $this->row('Maria Muster', 'maria.neu@example.com'),
            $this->row('Geloescht', 'weg@example.com'),
            $this->row('Hanna Ohne', ''),
            $this->row('Niemand', ''),
        ], decisions: [
            2 => 'create',
            3 => 'restore:'.$geloescht->id,
            4 => 'email:Hanna.Konto@example.com',
            5 => 'email:gibtsnicht@example.com',
        ]);

        $this->assertTrue($this->isMember(User::where('email', 'maria.neu@example.com')->firstOrFail()));
        $this->assertFalse($geloescht->fresh()->trashed());
        $this->assertTrue($this->isMember($geloescht));
        $this->assertTrue($this->isMember($ziel));
        $this->assertCount(1, $report->unassigned);
        $this->assertSame('Niemand', $report->unassigned[0]['name']);
        $this->assertStringContainsString('gibtsnicht@example.com', $report->unassigned[0]['reason']);
    }

    /** @test */
    public function auswertung_nennt_nicht_erreichbare_mitglieder(): void
    {
        $nieAngemeldet = $this->user('Nie Angemeldet', 'nie@example.com', ['changePassword' => true]);
        $ohneMail = $this->user('Ohne Mail', 'ohne@example.com');
        $ohneMail->assignRole('Vereinsmitglied');
        $ohneMail->groups()->attach($this->verein->id);
        $ohneMail->forceFill(['email' => ''])->saveQuietly();

        $report = $this->import([$this->row('Nie Angemeldet', 'nie@example.com'), $this->row('Kein Konto', '')], removeMissing: false);

        $this->assertEqualsCanonicalizing(['Nie Angemeldet', 'Ohne Mail'], array_column($report->unreachable, 'name'));
        $categories = array_column($report->evaluationRows(), 0);
        $this->assertSame(['Nicht zugeordnet', 'Nicht erreichbar', 'Nicht erreichbar'], $categories);
    }

    /** @test */
    public function vorschau_und_bestaetigung_ueber_die_oberflaeche(): void
    {
        Permission::firstOrCreate(['name' => 'import user', 'guard_name' => 'web']);
        $admin = $this->user('Admin', 'admin@example.com');
        $admin->givePermissionTo('import user');
        $this->actingAs($admin);

        $existing = $this->user('Anna Beispiel', 'anna@example.com');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['Person: Vorname Nachname', 'Person: E-Mail Privat'],
            ['Anna Beispiel', 'ANNA@example.com'],
            ['Neu Mitglied', 'neu@example.com'],
            ['Hanna Ohne', ''],
        ]);
        $hanna = $this->user('Hanna Konto', 'hanna@example.com');
        $tmpPath = storage_path('app/verein_import_test.xlsx');
        (new Xlsx($spreadsheet))->save($tmpPath);
        $file = new UploadedFile($tmpPath, 'verein.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $preview = $this->post('/users/importVerein', ['file' => $file, 'abgleich' => '1', 'send_email' => '1']);
        @unlink($tmpPath);

        $preview->assertOk()->assertSee('Vorschau')->assertSee('neu@example.com');
        $this->assertFalse($this->isMember($existing));
        $this->assertNull(User::where('email', 'neu@example.com')->first());

        $token = $preview->viewData('token');
        $this->get(route('users.import.verein.auswertung', $preview->viewData('evaluation')))->assertOk()->assertDownload();

        $result = $this->post(route('users.import.verein.confirm'), [
            'token' => $token, 'abgleich' => '1', 'send_email' => '1',
            'selection_sent' => '1',
            'selected' => ['user:'.$existing->id],
            'decision' => [4 => 'email'],
            'decision_email' => [4 => 'hanna@example.com'],
        ]);
        $result->assertOk()->assertSee('Der Import wurde ausgeführt')->assertSee('Neu Mitglied');

        $this->assertTrue($this->isMember($existing));
        $this->assertTrue($this->isMember($hanna));
        $this->assertNull(User::where('email', 'neu@example.com')->first());
        $this->assertFalse($this->isMember($admin));
        $this->get(route('users.import.verein.auswertung', $result->viewData('evaluation')))->assertOk();
    }
}

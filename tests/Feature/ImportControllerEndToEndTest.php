<?php

namespace Tests\Feature;

use App\Mail\ImportCredentialsMail;
use App\Mail\NewUserPasswordMail;
use App\Model\Child;
use App\Model\Group;
use App\Model\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImportControllerEndToEndTest extends TestCase
{
    /** @test */
    public function eltern_import_creates_users_links_sorg2_groups_and_child(): void
    {
        Mail::fake();

        Permission::firstOrCreate(['name' => 'import user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Eltern', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Aufnahme', 'guard_name' => 'web']);

        $admin = User::factory()->create(['changePassword' => false]);
        $admin->givePermissionTo('import user');
        $this->actingAs($admin);

        $klassenstufe = Group::create(['name' => 'Klassenstufe 5', 'protected' => 0]);
        $lerngruppe = Group::create(['name' => '5a', 'protected' => 0]);

        $child = Child::create([
            'first_name' => 'Kevin',
            'last_name'  => 'Mustermann',
            'group_id'   => $lerngruppe->id,
        ]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['gruppen', 'klassenstufe', 'lerngruppe', 'S1Vorname', 'S1Nachname', 'S1Email', 'S2Vorname', 'S2Nachname', 'S2Email', 'kind_vorname', 'kind_nachname'],
            ['Elternvertreter,Förderverein', '5', 'b5a', 'Max', 'Mustermann', 'max@example.com', 'Erika', 'Mustermann', 'erika@example.com', 'Kevin', 'Mustermann'],
        ]);

        $tmpPath = storage_path('app/e2e_import_test.xlsx');
        (new Xlsx($spreadsheet))->save($tmpPath);

        $file = new UploadedFile($tmpPath, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->post('/users/import', [
            'file'          => $file,
            'type'          => 'eltern',
            'send_email'    => '1',
            'klassenstufe'  => 2,
            'lerngruppe'    => 3,
            'S1Vorname'     => 4,
            'S1Nachname'    => 5,
            'S1Email'       => 6,
            'S2Vorname'     => 7,
            'S2Nachname'    => 8,
            'S2Email'       => 9,
            'gruppen'       => 1,
            'kind_vorname'  => 10,
            'kind_nachname' => 11,
            'new_groups'    => ['Elternvertreter', 'Förderverein'],
        ]);

        @unlink($tmpPath);

        // Debug output to diagnose unexpected redirect targets during test development.
        if ($response->getStatusCode() >= 300 && $response->getStatusCode() < 400) {
            fwrite(STDERR, "\nRedirect target: " . $response->headers->get('Location') . "\n");
            fwrite(STDERR, "Session errors: " . json_encode(session('errors') ? session('errors')->getMessages() : null) . "\n");
        }

        // send_email = '1' -> E-Mail-Modus: neue Benutzer erhalten eine Willkommens-E-Mail,
        // es wird keine PDF erzeugt/versendet, sondern normal auf die Benutzerliste umgeleitet.
        $response->assertRedirect(url('users'));

        $user1 = User::where('email', 'max@example.com')->first();
        $user2 = User::where('email', 'erika@example.com')->first();

        $this->assertNotNull($user1, 'Sorg1 wurde nicht angelegt');
        $this->assertNotNull($user2, 'Sorg2 wurde nicht angelegt');

        $this->assertTrue($user1->hasRole('Eltern'));
        $this->assertTrue($user2->hasRole('Eltern'));

        $elternvertreter = Group::where('name', 'Elternvertreter')->first();
        $foerderverein = Group::where('name', 'Förderverein')->first();

        $this->assertNotNull($elternvertreter, 'Gruppe "Elternvertreter" wurde nicht angelegt');
        $this->assertNotNull($foerderverein, 'Gruppe "Förderverein" wurde nicht angelegt');

        $this->assertTrue($user1->groups->contains($klassenstufe), 'Sorg1 nicht mit Klassenstufe verknüpft');
        $this->assertTrue($user1->groups->contains($lerngruppe), 'Sorg1 nicht mit Lerngruppe verknüpft');
        $this->assertTrue($user1->groups->contains($elternvertreter), 'Sorg1 nicht mit Elternvertreter verknüpft');
        $this->assertTrue($user1->groups->contains($foerderverein), 'Sorg1 nicht mit Förderverein verknüpft');

        $this->assertTrue($user2->groups->contains($klassenstufe), 'Sorg2 nicht mit Klassenstufe verknüpft');
        $this->assertTrue($user2->groups->contains($elternvertreter), 'Sorg2 nicht mit Elternvertreter verknüpft');

        $user1->refresh();
        $user2->refresh();
        $this->assertEquals($user2->id, $user1->sorg2, 'Sorg1 <-> Sorg2 nicht verknüpft');
        $this->assertEquals($user1->id, $user2->sorg2, 'Sorg2 <-> Sorg1 nicht verknüpft');

        $child->refresh();
        $this->assertTrue($child->parents->contains($user1), 'Kind nicht mit Sorg1 verknüpft');
        $this->assertTrue($child->parents->contains($user2), 'Kind nicht mit Sorg2 verknüpft');

        // Der Mailversand an neue Benutzer muss asynchron über die Queue erfolgen
        // (Mail::queue()), damit ein langsamer Mailserver den Import nicht ausbremst.
        Mail::assertQueued(NewUserPasswordMail::class, function ($mail) use ($user1) {
            return $mail->user->id === $user1->id;
        });
        Mail::assertQueued(NewUserPasswordMail::class, function ($mail) use ($user2) {
            return $mail->user->id === $user2->id;
        });
        Mail::assertNotSent(NewUserPasswordMail::class);
    }

    /** @test */
    public function eltern_import_with_pdf_mode_sends_no_user_mail_but_downloads_and_emails_admin(): void
    {
        Mail::fake();

        Permission::firstOrCreate(['name' => 'import user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Eltern', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Aufnahme', 'guard_name' => 'web']);

        $admin = User::factory()->create(['changePassword' => false, 'email' => 'admin@example.com']);
        $admin->givePermissionTo('import user');
        $this->actingAs($admin);

        Group::create(['name' => 'Klassenstufe 5', 'protected' => 0]);
        Group::create(['name' => '5a', 'protected' => 0]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['klassenstufe', 'lerngruppe', 'S1Vorname', 'S1Nachname', 'S1Email'],
            ['5', 'b5a', 'Peter', 'Petersen', 'peter@example.com'],
        ]);

        $tmpPath = storage_path('app/e2e_import_test_pdf.xlsx');
        (new Xlsx($spreadsheet))->save($tmpPath);

        $file = new UploadedFile($tmpPath, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->post('/users/import', [
            'file'         => $file,
            'type'         => 'eltern',
            'send_email'   => '0',
            'klassenstufe' => 1,
            'lerngruppe'   => 2,
            'S1Vorname'    => 3,
            'S1Nachname'   => 4,
            'S1Email'      => 5,
        ]);

        @unlink($tmpPath);

        // PDF-Modus: der Import-Response ist die PDF selbst (Download).
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));

        // Der neu angelegte Benutzer darf KEINE Willkommens-E-Mail erhalten.
        $newUser = User::where('email', 'peter@example.com')->first();
        $this->assertNotNull($newUser);
        Mail::assertNotSent(NewUserPasswordMail::class, function ($mail) use ($newUser) {
            return $mail->user->id === $newUser->id;
        });
        Mail::assertNotQueued(NewUserPasswordMail::class, function ($mail) use ($newUser) {
            return $mail->user->id === $newUser->id;
        });

        // Stattdessen wird die PDF zusätzlich an den einloggten (importierenden) Benutzer gemailt.
        Mail::assertQueued(ImportCredentialsMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });
    }
}

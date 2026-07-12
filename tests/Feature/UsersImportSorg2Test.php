<?php

namespace Tests\Feature;

use App\Imports\UsersImport;
use App\Model\Group;
use App\Model\User;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UsersImportSorg2Test extends TestCase
{
    /** @test */
    public function sorg2_user_is_created_role_assigned_grouped_and_linked(): void
    {
        Role::firstOrCreate(['name' => 'Eltern', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Aufnahme', 'guard_name' => 'web']);

        $group = Group::create(['name' => 'Klassenstufe 5', 'protected' => 0]);
        $lerngruppe = Group::create(['name' => '5a', 'protected' => 0]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['klassenstufe', 'lerngruppe', 'S1Vorname', 'S1Nachname', 'S1Email', 'S2Vorname', 'S2Nachname', 'S2Email'],
            ['5', 'b5a', 'Max', 'Mustermann', 'max@example.com', 'Erika', 'Mustermann', 'erika@example.com'],
        ]);

        $path = storage_path('app/test_sorg2_import.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        $header = [
            'klassenstufe' => 0,
            'lerngruppe'   => 1,
            'S1Vorname'    => 2,
            'S1Nachname'   => 3,
            'S1Email'      => 4,
            'S2Vorname'    => 5,
            'S2Nachname'   => 6,
            'S2Email'      => 7,
        ];

        $importer = new UsersImport($header, false);
        Excel::import($importer, $path);
        @unlink($path);

        $user1 = User::where('email', 'max@example.com')->first();
        $user2 = User::where('email', 'erika@example.com')->first();

        $this->assertNotNull($user1, 'Sorg1 wurde nicht angelegt');
        $this->assertNotNull($user2, 'Sorg2 wurde nicht angelegt');

        $this->assertTrue($user1->hasRole('Eltern'));
        $this->assertTrue($user2->hasRole('Eltern'));

        $this->assertTrue($user1->groups->contains($group));
        $this->assertTrue($user2->groups->contains($group));
        $this->assertTrue($user1->groups->contains($lerngruppe));
        $this->assertTrue($user2->groups->contains($lerngruppe));

        $user1->refresh();
        $user2->refresh();
        $this->assertEquals($user2->id, $user1->sorg2);
        $this->assertEquals($user1->id, $user2->sorg2);
    }

    /** @test */
    public function blank_sorg2_cell_does_not_create_a_bogus_user(): void
    {
        Role::firstOrCreate(['name' => 'Eltern', 'guard_name' => 'web']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['S1Vorname', 'S1Nachname', 'S1Email', 'S2Vorname', 'S2Nachname', 'S2Email'],
            ['Max', 'Mustermann', 'max@example.com', '', '', ''],
        ]);

        $path = storage_path('app/test_sorg2_blank_import.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        $header = [
            'S1Vorname'  => 0,
            'S1Nachname' => 1,
            'S1Email'    => 2,
            'S2Vorname'  => 3,
            'S2Nachname' => 4,
            'S2Email'    => 5,
        ];

        Excel::import(new UsersImport($header, false), $path);
        @unlink($path);

        $this->assertDatabaseHas('users', ['email' => 'max@example.com']);
        $this->assertDatabaseMissing('users', ['email' => '']);
        $this->assertEquals(1, User::count());
    }

    /** @test */
    public function sorg2_is_still_created_when_sorg1_fails(): void
    {
        Role::firstOrCreate(['name' => 'Eltern', 'guard_name' => 'web']);
        // 'Aufnahme'-Rolle absichtlich NICHT anlegen, damit removeRole() bei Sorg1 fehlschlägt
        // und wir prüfen können, dass Sorg2 trotzdem unabhängig angelegt wird.

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['S1Vorname', 'S1Nachname', 'S1Email', 'S2Vorname', 'S2Nachname', 'S2Email'],
            ['Max', 'Mustermann', 'max2@example.com', 'Erika', 'Mustermann', 'erika2@example.com'],
        ]);

        $path = storage_path('app/test_sorg2_independent_import.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        $header = [
            'S1Vorname'  => 0,
            'S1Nachname' => 1,
            'S1Email'    => 2,
            'S2Vorname'  => 3,
            'S2Nachname' => 4,
            'S2Email'    => 5,
        ];

        Excel::import(new UsersImport($header, false), $path);
        @unlink($path);

        $this->assertDatabaseHas('users', ['email' => 'erika2@example.com']);
    }
}

<?php

namespace Tests\Feature;

use App\Imports\UsersImport;
use App\Model\Child;
use App\Model\Group;
use App\Model\User;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UsersImportGroupChildTest extends TestCase
{
    private function importPath(string $name, array $header, array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(array_merge([$header], $rows));

        $path = storage_path('app/' . $name);
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    /** @test */
    public function missing_klassenstufe_and_lerngruppe_groups_are_created_and_child_is_assigned(): void
    {
        Role::firstOrCreate(['name' => 'Eltern', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Aufnahme', 'guard_name' => 'web']);

        $this->assertDatabaseMissing('groups', ['name' => 'Klassenstufe 6']);
        $this->assertDatabaseMissing('groups', ['name' => '6b']);

        $path = $this->importPath('test_group_create_import.xlsx', [
            'klassenstufe', 'lerngruppe', 'S1Vorname', 'S1Nachname', 'S1Email', 'kind_vorname', 'kind_nachname',
        ], [
            ['6', 'x6b', 'Anna', 'Beispiel', 'anna@example.com', 'Tim', 'Beispiel'],
        ]);

        $header = [
            'klassenstufe'   => 0,
            'lerngruppe'     => 1,
            'S1Vorname'      => 2,
            'S1Nachname'     => 3,
            'S1Email'        => 4,
            'kind_vorname'   => 5,
            'kind_nachname'  => 6,
        ];

        Excel::import(new UsersImport($header, false), $path);
        @unlink($path);

        $klassenstufe = Group::where('name', 'Klassenstufe 6')->first();
        $lerngruppe = Group::where('name', '6b')->first();

        $this->assertNotNull($klassenstufe, 'Klassenstufe wurde nicht automatisch angelegt');
        $this->assertNotNull($lerngruppe, 'Lerngruppe wurde nicht automatisch angelegt');

        $user = User::where('email', 'anna@example.com')->first();
        $this->assertTrue($user->groups->contains($klassenstufe));
        $this->assertTrue($user->groups->contains($lerngruppe));

        $child = Child::where('first_name', 'Tim')->where('last_name', 'Beispiel')->first();
        $this->assertNotNull($child, 'Kind wurde nicht angelegt');
        $this->assertEquals($klassenstufe->id, $child->group_id);
        $this->assertEquals($lerngruppe->id, $child->class_id);
    }

    /** @test */
    public function missing_lerngruppe_falls_back_to_klassenstufe_for_child_assignment(): void
    {
        Role::firstOrCreate(['name' => 'Eltern', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Aufnahme', 'guard_name' => 'web']);

        $path = $this->importPath('test_group_fallback_import.xlsx', [
            'klassenstufe', 'S1Vorname', 'S1Nachname', 'S1Email', 'kind_vorname', 'kind_nachname',
        ], [
            ['7', 'Peter', 'Muster', 'peter@example.com', 'Lea', 'Muster'],
        ]);

        $header = [
            'klassenstufe'  => 0,
            'S1Vorname'     => 1,
            'S1Nachname'    => 2,
            'S1Email'       => 3,
            'kind_vorname'  => 4,
            'kind_nachname' => 5,
        ];

        Excel::import(new UsersImport($header, false), $path);
        @unlink($path);

        $klassenstufe = Group::where('name', 'Klassenstufe 7')->first();
        $this->assertNotNull($klassenstufe);

        $child = Child::where('first_name', 'Lea')->where('last_name', 'Muster')->first();
        $this->assertNotNull($child, 'Kind wurde nicht angelegt');

        // Fehlt die Lerngruppe, wird ersatzweise die Klassenstufe für group_id UND class_id gesetzt.
        $this->assertEquals($klassenstufe->id, $child->group_id);
        $this->assertEquals($klassenstufe->id, $child->class_id);
    }

    /** @test */
    public function existing_child_without_class_gets_it_added_on_reimport(): void
    {
        Role::firstOrCreate(['name' => 'Eltern', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Aufnahme', 'guard_name' => 'web']);

        $child = Child::create(['first_name' => 'Nils', 'last_name' => 'Ohne']);
        $this->assertNull($child->group_id);
        $this->assertNull($child->class_id);

        $path = $this->importPath('test_group_existing_child_import.xlsx', [
            'klassenstufe', 'lerngruppe', 'S1Vorname', 'S1Nachname', 'S1Email', 'kind_vorname', 'kind_nachname',
        ], [
            ['8', 'x8c', 'Sina', 'Nachname', 'sina@example.com', 'Nils', 'Ohne'],
        ]);

        $header = [
            'klassenstufe'  => 0,
            'lerngruppe'    => 1,
            'S1Vorname'     => 2,
            'S1Nachname'    => 3,
            'S1Email'       => 4,
            'kind_vorname'  => 5,
            'kind_nachname' => 6,
        ];

        Excel::import(new UsersImport($header, false), $path);
        @unlink($path);

        $child->refresh();
        $klassenstufe = Group::where('name', 'Klassenstufe 8')->first();
        $lerngruppe = Group::where('name', '8c')->first();

        $this->assertEquals($klassenstufe->id, $child->group_id);
        $this->assertEquals($lerngruppe->id, $child->class_id);
    }

    /** @test */
    public function klassenstufe_zero_is_not_treated_as_missing(): void
    {
        // Regressionstest: PHP behandelt die Zeichenkette "0" mit empty() fälschlicherweise
        // als "leer". Eine Klassenstufe "0" (z.B. Vorschulklasse) oder eine Lerngruppe, die nach
        // Entfernen des führenden Zeichens "0" ergibt, darf daher nicht verloren gehen.
        Role::firstOrCreate(['name' => 'Eltern', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Aufnahme', 'guard_name' => 'web']);

        $path = $this->importPath('test_group_zero_import.xlsx', [
            'klassenstufe', 'lerngruppe', 'S1Vorname', 'S1Nachname', 'S1Email', 'kind_vorname', 'kind_nachname',
        ], [
            ['0', 'x0', 'Otto', 'Null', 'otto@example.com', 'Ottilie', 'Null'],
        ]);

        $header = [
            'klassenstufe'  => 0,
            'lerngruppe'    => 1,
            'S1Vorname'     => 2,
            'S1Nachname'    => 3,
            'S1Email'       => 4,
            'kind_vorname'  => 5,
            'kind_nachname' => 6,
        ];

        Excel::import(new UsersImport($header, false), $path);
        @unlink($path);

        $klassenstufe = Group::where('name', 'Klassenstufe 0')->first();
        $lerngruppe = Group::where('name', '0')->first();

        $this->assertNotNull($klassenstufe, 'Klassenstufe "0" wurde nicht angelegt');
        $this->assertNotNull($lerngruppe, 'Lerngruppe "0" wurde nicht angelegt');

        $child = Child::where('first_name', 'Ottilie')->where('last_name', 'Null')->first();
        $this->assertNotNull($child, 'Kind wurde nicht angelegt');
        $this->assertEquals($klassenstufe->id, $child->group_id);
        $this->assertEquals($lerngruppe->id, $child->class_id);
    }

    /** @test */
    public function no_group_with_empty_name_is_created(): void
    {
        // Regressionstest: Besteht die Lerngruppen-Spalte nur aus dem zu entfernenden
        // Präfixzeichen (z.B. "b" statt "b5a") oder ist die Gruppen-Spalte nur mit Kommas/
        // Leerzeichen befüllt, darf niemals eine Gruppe mit leerem Namen angelegt werden.
        Role::firstOrCreate(['name' => 'Eltern', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Aufnahme', 'guard_name' => 'web']);

        $path = $this->importPath('test_group_empty_name_import.xlsx', [
            'klassenstufe', 'lerngruppe', 'gruppen', 'S1Vorname', 'S1Nachname', 'S1Email',
        ], [
            ['9', 'b', ' , ', 'Karl', 'Fehler', 'karl@example.com'],
        ]);

        $header = [
            'klassenstufe' => 0,
            'lerngruppe'   => 1,
            'gruppen'      => 2,
            'S1Vorname'    => 3,
            'S1Nachname'   => 4,
            'S1Email'      => 5,
        ];

        Excel::import(new UsersImport($header, false), $path);
        @unlink($path);

        $this->assertDatabaseMissing('groups', ['name' => '']);

        $user = User::where('email', 'karl@example.com')->first();
        $this->assertNotNull($user);
        // Nur die gültige Klassenstufe darf zugewiesen sein, keine leere Gruppe.
        $this->assertTrue($user->groups->contains('name', 'Klassenstufe 9'));
        $this->assertFalse($user->groups->contains('name', ''));
    }
}

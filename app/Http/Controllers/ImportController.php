<?php

namespace App\Http\Controllers;

use App\Exports\AufnahmeImportVorlage;
use App\Imports\AufnahmeImport;
use App\Imports\MitarbeiterImport;
use App\Imports\UsersImport;
use App\Imports\VereinImport;
use App\Exports\ElternImportVorlage;
use App\Exports\MitarbeiterImportVorlage;
use App\Exports\VereinImportVorlage;
use App\Model\Group;
use App\Model\group_user;
use App\Scopes\GetGroupsScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Excel as ExcelFormat;

class ImportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            ['permission:import user'],
        ];
    }

    public function importForm()
    {
        return view('user.import');
    }

    /**
     * AJAX: Reads the header row from an uploaded Excel file and returns column names as JSON.
     */
    public function previewHeaders(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,ods,csv', 'max:10240'],
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getPathname());
            $sheet       = $spreadsheet->getActiveSheet();

            $headers = [];
            foreach ($sheet->getRowIterator(1, 1) as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $headers[] = (string) ($cell->getValue() ?? '');
                }
            }

            // Remove trailing empty columns
            while (! empty($headers) && end($headers) === '') {
                array_pop($headers);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Fehler beim Lesen der Datei: ' . $e->getMessage()], 422);
        }

        return response()->json(['headers' => $headers]);
    }

    /**
     * AJAX: Liest die "Gruppen"-Spalte einer hochgeladenen Excel-Datei aus, ermittelt
     * alle darin vorkommenden (Semikolon-getrennten) Gruppennamen und markiert, welche
     * davon bereits als Gruppe existieren. Damit kann der Benutzer vor dem Import
     * auswählen, welche der neuen Gruppennamen als globale Gruppe angelegt werden sollen.
     */
    public function previewGroups(Request $request): JsonResponse
    {
        $request->validate([
            'file'            => ['required', 'file', 'mimes:xlsx,xls,ods,csv', 'max:10240'],
            'gruppen_column'  => ['required', 'integer', 'min:1'],
        ]);

        $columnIndex = (int) $request->input('gruppen_column');

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getPathname());
            $sheet       = $spreadsheet->getActiveSheet();
            $highestRow  = $sheet->getHighestDataRow();

            $names = [];
            for ($row = 2; $row <= $highestRow; $row++) {
                $value = $sheet->getCellByColumnAndRow($columnIndex, $row)->getValue();
                if ($value === null || $value === '') {
                    continue;
                }
                foreach (explode(',', (string) $value) as $name) {
                    $name = trim($name);
                    if ($name !== '') {
                        $names[$name] = $name;
                    }
                }
            }
            ksort($names, SORT_NATURAL | SORT_FLAG_CASE);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Fehler beim Lesen der Datei: ' . $e->getMessage()], 422);
        }

        $existingNames = Group::withoutGlobalScope(GetGroupsScope::class)
            ->pluck('name')
            ->map(fn ($n) => mb_strtolower($n))
            ->all();

        $groups = array_map(function ($name) use ($existingNames) {
            return [
                'name'   => $name,
                'exists' => in_array(mb_strtolower($name), $existingNames, true),
            ];
        }, array_values($names));

        return response()->json(['groups' => $groups]);
    }

    /**
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function import(Request $request)
    {
        $type = $request->input('type');

        $rules = [
            'file'          => ['required', 'file', 'mimes:xlsx,xls,ods,csv', 'max:10240'],
            'type'          => ['required', 'in:eltern,aufnahme,mitarbeiter'],
            'send_email'    => ['nullable', 'in:1,0'],
            'new_groups'    => ['nullable', 'array'],
            'new_groups.*'  => ['string', 'max:255'],
        ];

        if ($type === 'eltern') {
            $rules['klassenstufe'] = ['required', 'integer', 'min:1'];
            $rules['lerngruppe'] = ['required', 'integer', 'min:1'];
            $rules['S1Vorname'] = ['required', 'integer', 'min:1'];
            $rules['S1Nachname'] = ['required', 'integer', 'min:1'];
            $rules['S1Email'] = ['required', 'integer', 'min:1'];
            $rules['S2Vorname'] = ['nullable', 'integer', 'min:1'];
            $rules['S2Nachname'] = ['nullable', 'integer', 'min:1'];
            $rules['S2Email'] = ['nullable', 'integer', 'min:1'];
            $rules['gruppen'] = ['nullable', 'integer', 'min:1'];
            $rules['kind_vorname'] = ['nullable', 'integer', 'min:1'];
            $rules['kind_nachname'] = ['nullable', 'integer', 'min:1'];
        } elseif ($type === 'aufnahme') {
            $rules['S1Vorname'] = ['required', 'integer', 'min:1'];
            $rules['S1Nachname'] = ['required', 'integer', 'min:1'];
            $rules['S1Email'] = ['required', 'integer', 'min:1'];
            $rules['S2Vorname'] = ['nullable', 'integer', 'min:1'];
            $rules['S2Nachname'] = ['nullable', 'integer', 'min:1'];
            $rules['S2Email'] = ['nullable', 'integer', 'min:1'];
            $rules['gruppen'] = ['nullable', 'integer', 'min:1'];
            $rules['kind_vorname'] = ['nullable', 'integer', 'min:1'];
            $rules['kind_nachname'] = ['nullable', 'integer', 'min:1'];
        }

        $validated = $request->validate($rules);
        $sendEmail = ($validated['send_email'] ?? '1') === '1';

        if (! $request->hasFile('file')) {
            return redirect()->back()->with([
                'type'    => 'danger',
                'Meldung' => 'Keine Datei ausgewählt',
            ]);
        }

        $importTypeLabel = match ($validated['type']) {
            'eltern'      => 'Eltern-Import',
            'aufnahme'    => 'Aufnahme-Import',
            'mitarbeiter' => 'Mitarbeiter-Import',
        };

        $uploadedFile = $request->file('file');
        $tmpDir       = storage_path('app/imports/temp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        $filename   = 'import_' . now()->format('YmdHis') . '_' . uniqid() . '.' . $uploadedFile->getClientOriginalExtension();
        $movedFile  = $uploadedFile->move($tmpDir, $filename);
        $storedPath = $movedFile->getPathname();

        // Schutz vor PHP 8.4 ValueError "Path must not be empty": Falls das Verschieben
        // der Datei aus irgendeinem Grund keinen gültigen Pfad ergeben hat, brechen wir
        // hier kontrolliert ab, statt Excel::import() mit einem leeren Pfad aufzurufen.
        if ($storedPath === '' || ! is_file($storedPath)) {
            return redirect()->back()->with([
                'type'    => 'danger',
                'Meldung' => 'Die Datei konnte nicht verarbeitet werden. Bitte wählen Sie die Datei erneut aus und versuchen Sie es noch einmal.',
            ]);
        }

        // Vom Benutzer ausgewählte neue Gruppennamen (aus der "Gruppen"-Spalte) werden
        // vor dem eigentlichen Import als globale Gruppe (ohne owner_id) angelegt, damit
        // der Importer sie den Benutzern zuordnen kann.
        foreach ($validated['new_groups'] ?? [] as $groupName) {
            $groupName = trim($groupName);
            if ($groupName === '') {
                continue;
            }
            Group::withoutGlobalScope(GetGroupsScope::class)->firstOrCreate(
                ['name' => $groupName],
                ['protected' => 0]
            );
        }

        try {
        if ($validated['type'] === 'eltern') {
            foreach (Group::where('protected', 0)->get() as $group) {
                $group->users()->detach();
            }

            $header = [
                'klassenstufe' => $validated['klassenstufe'] - 1,
                'lerngruppe'   => $validated['lerngruppe'] - 1,
                'S1Vorname'    => $validated['S1Vorname'] - 1,
                'S1Nachname'   => $validated['S1Nachname'] - 1,
                'S1Email'      => $validated['S1Email'] - 1,
            ];
            if (! empty($validated['S2Vorname']))     $header['S2Vorname']    = $validated['S2Vorname'] - 1;
            if (! empty($validated['S2Nachname']))    $header['S2Nachname']   = $validated['S2Nachname'] - 1;
            if (! empty($validated['S2Email']))       $header['S2Email']      = $validated['S2Email'] - 1;
            if (! empty($validated['gruppen']))       $header['gruppen']      = $validated['gruppen'] - 1;
            if (! empty($validated['kind_vorname']))  $header['kind_vorname'] = $validated['kind_vorname'] - 1;
            if (! empty($validated['kind_nachname'])) $header['kind_nachname']= $validated['kind_nachname'] - 1;

            $importer = new UsersImport($header, $sendEmail);

            $this->debugExcelImport($importer, $storedPath);
            $newUsers = $importer->getNewUsers();
            $Meldung  = 'Eltern wurden importiert';
        } elseif ($validated['type'] === 'aufnahme') {
            $header = [
                'S1Vorname'  => $validated['S1Vorname'] - 1,
                'S1Nachname' => $validated['S1Nachname'] - 1,
                'S1Email'    => $validated['S1Email'] - 1,
            ];
            if (! empty($validated['S2Vorname']))     $header['S2Vorname']    = $validated['S2Vorname'] - 1;
            if (! empty($validated['S2Nachname']))    $header['S2Nachname']   = $validated['S2Nachname'] - 1;
            if (! empty($validated['S2Email']))       $header['S2Email']      = $validated['S2Email'] - 1;
            if (! empty($validated['gruppen']))       $header['gruppen']      = $validated['gruppen'] - 1;
            if (! empty($validated['kind_vorname']))  $header['kind_vorname'] = $validated['kind_vorname'] - 1;
            if (! empty($validated['kind_nachname'])) $header['kind_nachname']= $validated['kind_nachname'] - 1;

            $importer = new AufnahmeImport($header, $sendEmail);
            $this->debugExcelImport($importer, $storedPath);
            $newUsers = $importer->getNewUsers();
            $Meldung  = 'Aufnahme-Import abgeschlossen';
        } else {
            $importer = new MitarbeiterImport($sendEmail);
            $this->debugExcelImport($importer, $storedPath);
            $newUsers = $importer->getNewUsers();
            $Meldung  = 'Mitarbeiter-Import abgeschlossen';
        }
        } finally {
            if (file_exists($storedPath)) {
                unlink($storedPath);
            }
        }

        if (! $sendEmail && count($newUsers) > 0) {
            $pdf = Pdf::loadView('pdf.import-credentials', [
                'users'      => $newUsers,
                'importType' => $importTypeLabel,
            ]);
            $filename = 'zugangsdaten-' . now()->format('Y-m-d_H-i') . '.pdf';
            return $pdf->download($filename);
        }

        return redirect()->to(url('users'))->with([
            'type'    => 'success',
            'Meldung' => $Meldung . ($sendEmail ? '' : ' – keine neuen Benutzer angelegt.'),
        ]);
    }

    /**
     * TEMPORÄR: Führt Excel::import() aus und protokolliert bei einem Fehler
     * die vollständige Exception (Klasse, Nachricht, Datei, Zeile, Trace) im
     * Laravel-Log, damit der tatsächliche Ursprungsort des "Path must not be
     * empty"-Fehlers ermittelt werden kann. Kann nach der Fehlersuche wieder
     * entfernt werden.
     */
    private function debugExcelImport(object $importer, string $storedPath): void
    {
        try {
            \Log::info('debugExcelImport: Starte Import', [
                'storedPath' => $storedPath,
                'exists'     => file_exists($storedPath),
                'realpath'   => realpath($storedPath),
                'is_file'    => is_file($storedPath),
                'filesize'   => @filesize($storedPath),
            ]);

            Excel::import($importer, $storedPath);

            \Log::info('debugExcelImport: Import erfolgreich');
        } catch (\Throwable $e) {
            \Log::error('debugExcelImport: Fehler beim Import', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function importVereinForm()
    {
        return view('user.importVerein');
    }

    // ─── Vorlagen-Downloads ───────────────────────────────────────────────────

    public function downloadElternVorlage()
    {
        return Excel::download(new ElternImportVorlage(), 'eltern-import-vorlage.ods', ExcelFormat::ODS);
    }

    public function downloadAufnahmeVorlage()
    {
        return Excel::download(new AufnahmeImportVorlage(), 'aufnahme-import-vorlage.ods', ExcelFormat::ODS);
    }

    public function downloadMitarbeiterVorlage()
    {
        return Excel::download(new MitarbeiterImportVorlage(), 'mitarbeiter-import-vorlage.ods', ExcelFormat::ODS);
    }

    public function downloadVereinVorlage()
    {
        return Excel::download(new VereinImportVorlage(), 'verein-import-vorlage.ods', ExcelFormat::ODS);
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function importVerein(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,ods,csv', 'max:10240'],
        ]);

        if ($request->hasFile('file')) {

            $group = Group::firstOrCreate(['name' => 'Vereinsmitglied'], [
                'protected' => 1,
                'bereich' => 'Verein',
            ]);

            group_user::where('group_id', $group->id)->delete();

            $role = Role::firstOrCreate(['name' => 'Vereinsmitglied'], [
                'guard_name' => 'web',
            ]);

            foreach ($role->users as $user) {
                $user->removeRole($role);
            }

            Excel::import(new VereinImport($group), $request->file('file'));
            $Meldung = 'Mitglieder wurden importiert';

            return redirect()->to(url('users'))->with([
                'type' => 'success',
                'Meldung' => $Meldung,
            ]);
        } else {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Keine Datei ausgewählt',
            ]);
        }
    }
}

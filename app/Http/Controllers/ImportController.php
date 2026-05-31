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
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Maatwebsite\Excel\Facades\Excel;
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

    /**
     */
    public function importForm()
    {
        return view('user.import');
    }

    /**
     * @return RedirectResponse
     */
    public function import(Request $request)
    {
        // Spalten-Indizes, die in den verschiedenen Import-Typen verwendet werden.
        // Sie müssen Ganzzahlen >= 1 sein, da im Code "- 1" gerechnet wird.
        $columnFields = [
            'klassenstufe', 'lerngruppe',
            'S1Vorname', 'S1Nachname', 'S1Email',
            'S2Vorname', 'S2Nachname', 'S2Email',
            'gruppen',
        ];

        $rules = [
            'file' => ['required', 'file', 'mimes:xlsx,xls,ods,csv', 'max:10240'],
            'type' => ['required', 'in:eltern,aufnahme,mitarbeiter'],
        ];

        // Spaltenangaben nur für die Typen verlangen, die sie nutzen
        if (in_array($request->input('type'), ['eltern', 'aufnahme'], true)) {
            foreach ($columnFields as $field) {
                $rules[$field] = ['required', 'integer', 'min:1'];
            }
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('file')) {
            if ($validated['type'] == 'eltern') {
                // group_user::truncate();

                foreach (Group::where('protected', 0)->get() as $group) {
                    $group->users()->detach();
                }

                $header = [
                    'klassenstufe' => $validated['klassenstufe'] - 1,
                    'lerngruppe' => $validated['lerngruppe'] - 1,
                    'S1Vorname' => $validated['S1Vorname'] - 1,
                    'S1Nachname' => $validated['S1Nachname'] - 1,
                    'S1Email' => $validated['S1Email'] - 1,
                    'S2Email' => $validated['S2Email'] - 1,
                    'S2Vorname' => $validated['S2Vorname'] - 1,
                    'S2Nachname' => $validated['S2Nachname'] - 1,
                    'gruppen' => $validated['gruppen'] - 1,
                ];

                Excel::import(new UsersImport($header), $request->file('file'));

                $Meldung = 'Eltern wurden importiert';
            } elseif ($validated['type'] == 'aufnahme') {
                $header = [
                    'S1Vorname' => $validated['S1Vorname'] - 1,
                    'S1Nachname' => $validated['S1Nachname'] - 1,
                    'S1Email' => $validated['S1Email'] - 1,
                    'S2Email' => $validated['S2Email'] - 1,
                    'S2Vorname' => $validated['S2Vorname'] - 1,
                    'S2Nachname' => $validated['S2Nachname'] - 1,
                    'gruppen' => $validated['gruppen'] - 1,
                ];

                Excel::import(new AufnahmeImport($header), $request->file('file'));
                $Meldung = 'Aufnahme-Import abgeschlossen';
            } else {
                Excel::import(new MitarbeiterImport, $request->file('file'));
                $Meldung = 'Mitarbeiter-Import abgeschlossen';
            }

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

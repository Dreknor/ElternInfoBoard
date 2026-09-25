<?php

namespace App\Http\Controllers;

use App\Exports\AufnahmeImportVorlage;
use App\Exports\SchuelerImportVorlage;
use App\Imports\AufnahmeImport;
use App\Imports\MitarbeiterImport;
use App\Imports\SchuelerImportRows;
use App\Imports\UsersImport;
use App\Services\Import\SchuelerImportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        if ($request->hasFile('file')) {
            if ($request->input('type') == 'schueler') {
                return $this->previewSchuelerImport($request);
            }

            if ($request->input('type') == 'eltern') {
                // Nur manuelle Mitgliedschaften nicht geschützter Gruppen leeren – aus Kindern
                // abgeleitete Mitgliedschaften (is_auto_provisioned) bleiben erhalten.
                DB::table('group_user')
                    ->whereIn('group_id', Group::withoutGlobalScopes()->where('protected', 0)->pluck('id'))
                    ->where('is_auto_provisioned', false)
                    ->delete();

                $header = [
                    'klassenstufe' => $request->klassenstufe - 1,
                    'lerngruppe' => $request->lerngruppe - 1,
                    'S1Vorname' => $request->S1Vorname - 1,
                    'S1Nachname' => $request->S1Nachname - 1,
                    'S1Email' => $request->S1Email - 1,
                    'S2Email' => $request->S2Email - 1,
                    'S2Vorname' => $request->S2Vorname - 1,
                    'S2Nachname' => $request->S2Nachname - 1,
                    'gruppen' => $request->gruppen - 1,
                ];

                Excel::import(new UsersImport($header), $request->file('file'));

                $Meldung = 'Eltern wurden importiert';
            } elseif ($request->input('type') == 'aufnahme') {
                $header = [
                    'S1Vorname' => $request->input('S1Vorname') - 1,
                    'S1Nachname' => $request->input('S1Nachname') - 1,
                    'S1Email' => $request->input('S1Email') - 1,
                    'S2Email' => $request->input('S2Email') - 1,
                    'S2Vorname' => $request->input('S2Vorname') - 1,
                    'S2Nachname' => $request->input('S2Nachname') - 1,
                    'gruppen' => $request->input('gruppen') - 1,
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

    /**
     * Schritt 1 des Schüler-Imports: Datei ablegen und Probelauf anzeigen.
     */
    private function previewSchuelerImport(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xls,xlsx,ods,csv']]);

        $path = $request->file('file')->store('imports');
        $report = app(SchuelerImportService::class)->run($this->schuelerRows($path), dryRun: true, markLeavers: $request->boolean('abgaenger'));

        return view('user.importPreview', [
            'report' => $report,
            'token' => basename($path),
            'abgaenger' => $request->boolean('abgaenger'),
        ]);
    }

    /**
     * Schritt 2: Import nach Bestätigung ausführen.
     */
    public function confirmSchuelerImport(Request $request)
    {
        $data = $request->validate(['token' => ['required', 'string', 'regex:/^[A-Za-z0-9._-]+$/']]);
        $path = 'imports/'.$data['token'];

        if (! Storage::exists($path)) {
            return redirect()->to(url('users/import'))->with(['type' => 'danger', 'Meldung' => 'Importdatei nicht mehr vorhanden – bitte erneut hochladen.']);
        }

        $report = app(SchuelerImportService::class)->run($this->schuelerRows($path), dryRun: false, markLeavers: $request->boolean('abgaenger'));
        Storage::delete($path);

        return view('user.importPreview', [
            'report' => $report,
            'token' => null,
            'abgaenger' => $request->boolean('abgaenger'),
        ]);
    }

    public function downloadSchuelerVorlage()
    {
        return Excel::download(new SchuelerImportVorlage(), 'schueler-import-vorlage.ods', ExcelFormat::ODS);
    }

    private function schuelerRows(string $path): array
    {
        $sheets = Excel::toArray(new SchuelerImportRows, Storage::path($path));

        return $sheets[0] ?? [];
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

<?php

namespace App\Http\Controllers;

use App\Imports\AufnahmeImport;
use App\Imports\MitarbeiterImport;
use App\Imports\UsersImport;
use App\Model\group_user;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class ImportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:import user']);
    }

    public function importForm()
    {
        return view('user.import');
    }

    public function import(Request $request)
    {
        if ($request->hasFile('file')) {
            if ($request->input('type') == 'eltern') {
                group_user::truncate();

                $header = [
                    'klassenstufe' => $request->input('klassenstufe') - 1,
                    'lerngruppe' => $request->input('lerngruppe') - 1,
                    'S1Vorname' => $request->input('S1Vorname') - 1,
                    'S1Nachname' => $request->input('S1Nachname') - 1,
                    'S1Email' => $request->input('S1Email') - 1,
                    'S2Email' => $request->input('S2Email') - 1,
                    'S2Vorname' => $request->input('S2Vorname') - 1,
                    'S2Nachname' => $request->input('S2Nachname') - 1,
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
                Excel::import(new MitarbeiterImport(), $request->file('file'));
                $Meldung = 'Mitarbeiter-Import abgeschlossen';
            }

            return redirect()->to(url('users'))->with([
                'type'  => 'success',
                'Meldung'   => $Meldung,
            ]);
        } else {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Keine Datei ausgewählt',
            ]);
        }
    }
}

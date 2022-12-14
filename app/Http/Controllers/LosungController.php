<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportLosungenRequest;
use App\Imports\LosungenImport;
use App\Imports\UsersImport;
use App\Model\Losung;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class LosungController extends Controller
{

    public function importView()
    {
        if (!auth()->user()->can('edit settings')) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt'
            ]);
        }
        return view('losung.import');
    }

    public function import(ImportLosungenRequest $request)
    {
        if (!auth()->user()->can('edit settings')) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt'
            ]);
        }

        Excel::import(new LosungenImport(), $request->file('files'));

        return redirect(url('/'))->with([
            'type' => 'success',
            'Meldung' => 'Losungen wurden importiert'
        ]);

    }

    public function getImage()
    {
        return response()->view('losung.image', [
            'losung' => Losung::where('date', Carbon::today())->first(),
        ])
            ->header('Content-type', 'image/jepg');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportLosungenRequest;
use App\Imports\LosungenImport;
use App\Model\Losung;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Maatwebsite\Excel\Facades\Excel;

class LosungController extends Controller
{

    /**
     * @return View|RedirectResponse
     */
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

    /**
     * @param ImportLosungenRequest $request
     * @return Application|RedirectResponse|Redirector
     */
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

    /**
     * @return Response
     */
    public function getImage()
    {
        return response()->view('losung.image', [
            'losung' => Losung::where('date', Carbon::today())->first(),
        ])
            ->header('Content-type', 'image/jepg');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\KrankmeldungRequest;
use App\Mail\DailyReportKrankmeldungen;
use App\Mail\krankmeldung;
use App\Model\krankmeldungen;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class KrankmeldungenController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(Request $request)
    {
        $krankmeldungen = $request->user()->krankmeldungen->paginate(15);

        return view('krankmeldung.index', [
            'krankmeldungen' => $krankmeldungen,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     */
    public function store(KrankmeldungRequest $request)
    {
        $krankmeldung = new krankmeldungen();
        $krankmeldung->fill($request->validated());
        $krankmeldung->users_id = auth()->id();
        $krankmeldung->save();

        Mail::to(config('mail.from.address'))
            ->cc($request->user()->email)
            ->queue(new krankmeldung($request->user()->email, $request->user()->name, $request->name, Carbon::createFromFormat('Y-m-d', $request->start)->format('d.m.Y'), Carbon::createFromFormat('Y-m-d', $request->ende)->format('d.m.Y'), $request->kommentar));

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Krankmeldung wurde gespeichert',
        ]);
    }

    public function dailyReport()
    {
        $krankmeldungen = krankmeldungen::where('start', '<=', Carbon::now()->format('Y-m-d'))
            ->where('ende', '>=', Carbon::now()->format('Y-m-d'))
            ->get();

        Mail::to(config('mail.from.address'))
            ->queue(new DailyReportKrankmeldungen($krankmeldungen));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  krankmeldungen  $krankmeldungen
     * @return Response
     */
    public function destroy(krankmeldungen $krankmeldungen)
    {
        //
    }
}

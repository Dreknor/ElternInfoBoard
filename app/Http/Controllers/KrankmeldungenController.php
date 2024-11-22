<?php

namespace App\Http\Controllers;

use App\Http\Requests\KrankmeldungRequest;
use App\Mail\DailyReportKrankmeldungen;
use App\Mail\krankmeldung;
use App\Model\ActiveDisease;
use App\Model\Disease;
use App\Model\krankmeldungen;
use App\Model\Module;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
        $krankmeldungen = $request->user()->krankmeldungen->load('user')->paginate(15);

        if (Module::where('setting', 'meldepfl. Erkrankungen')->first()?->options['active'] == 1) {
            $diseases = Cache::remember('diseases', 60 * 60 * 24, function () {
                return Disease::all('id', 'name');
            });
        }

        return view('krankmeldung.index', [
            'krankmeldungen' => $krankmeldungen,
            'diseases' => $diseases ?? false,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param KrankmeldungRequest $request
     * @return RedirectResponse
     */
    public function store(KrankmeldungRequest $request)
    {
        try {
            $krankmeldung = new krankmeldungen();
            $krankmeldung->fill($request->validated());
            $krankmeldung->users_id = auth()->id();
            $krankmeldung->save();

            $meldung = "Krankmeldung wurde erfolgreich eingetragen";

            if ($request->disease_id != 0) {

                $disease = Disease::find($request->disease_id);
                ActiveDisease::insert([
                    'user_id' => auth()->id(),
                    'disease_id' => $request->disease_id,
                    'start' => $request->start,
                    'end' => $krankmeldung->start->addDays($disease->aushang_dauer),
                    'comment' => $request->kommentar,
                    'active' => false,
                ]);

                $meldung .= "Bitte beachten Sie folgende Hinweise: Wiederzulassung durch: " . $disease->wiederzulassung_durch . "

                Wiederzulassung wann: " . $disease->wiederzulassung_wann;
                Cache::forget('active_diseases');
            }

            Mail::to(config('mail.from.address'))
                ->cc($request->user()->email)
                ->queue(new krankmeldung($request->user()->email, $request->user()->name, $request->name, Carbon::createFromFormat('Y-m-d', $request->start)->format('d.m.Y'), Carbon::createFromFormat('Y-m-d', $request->ende)->format('d.m.Y'), $request->kommentar, $disease->name ?? null));

            return redirect()->back()->with([
                'type' => 'success',
                'Meldung' => $meldung,
            ]);
        } catch (\Exception $e) {

            Log::error('Krankmeldung: Fehler beim Erstellen der Krankmeldung: ' . $e->getMessage());

            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Fehler beim Erstellen der Krankmeldung. Bitte versuchen Sie es erneut',
            ]);
        }



    }

    /**
     * versendet täglich die derzeit erkrankten SuS
     * @return void
     */
    public function dailyReport()
    {
        $krankmeldungen = krankmeldungen::where('start', '<=', Carbon::now()->format('Y-m-d'))
            ->where('ende', '>=', Carbon::now()->format('Y-m-d'))
            ->get();

        Mail::to(config('mail.from.address'))
            ->queue(new DailyReportKrankmeldungen($krankmeldungen));
    }

    public function download()
    {
        if (auth()->user()->can('download krankmeldungen')) {
            $pdf = PDF::loadView('pdf.krankmeldungen', [
                'meldungen' => krankmeldungen::query()->whereDate('start', '<=', Carbon::today()->format('Y-m-d'))->whereDate('ende', '>=', Carbon::today()->format('Y-m-d'))->get(),
            ]);

            return $pdf->download(Carbon::now()->format('Y-m-d') . '_Krankmeldungen.pdf');
        }

        return redirect()->back()->with([
            'type' => 'warning',
            'Meldung' => "Berechtigung fehlt"
        ]);
    }

}

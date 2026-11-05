<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Imports\SchuelerImportRows;
use App\Model\User;
use App\Services\Students\StudentCodeSheet;
use App\Services\Students\StudentImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Schülerkonten-Import: Hochladen → Vorschau → Bestätigen → Zugangszettel (§9.1, S-07b).
 */
class StudentImportController extends Controller
{
    public function __construct(private readonly StudentImportService $import) {}

    public function show(Request $request): View|StreamedResponse|Response
    {
        if ($request->boolean('vorlage')) {
            return response()->streamDownload(function () {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['Schüler-ID', 'Benutzername', 'Konto'], ';');
                fputcsv($out, ['12345', '', 'ja'], ';');
                fputcsv($out, ['12346', 'max.muster', 'ja'], ';');
                fputcsv($out, ['12347', '', 'nein'], ';');
                fclose($out);
            }, 'schuelerkonten-import-vorlage.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        if ($request->filled('ergebnis')) {
            return $this->downloadCodes($request, (string) $request->query('ergebnis'));
        }

        return view('student.admin.import', ['result' => null, 'token' => null]);
    }

    public function preview(Request $request): View
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xls,xlsx,ods,csv,txt', 'max:5120']]);

        $path = $request->file('file')->storeAs('imports', 'schuelerkonten_'.Str::random(32).'.'.($request->file('file')->getClientOriginalExtension() ?: 'csv'));

        return view('student.admin.import', [
            'result' => $this->import->run($this->rows($path), dryRun: true),
            'token' => basename($path),
            'done' => false,
        ]);
    }

    public function execute(Request $request): View|RedirectResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'regex:/^schuelerkonten_[A-Za-z0-9]{32}\.[a-z]{2,4}$/']]);
        $path = 'imports/'.$data['token'];

        if (! Storage::exists($path)) {
            return redirect()->route('schuelerkonten.import')->with(['type' => 'danger', 'Meldung' => 'Importdatei nicht mehr vorhanden. Bitte erneut hochladen.']);
        }

        $result = $this->import->run($this->rows($path), dryRun: false);
        Storage::delete($path);

        $resultId = null;
        if ($result['created']->isNotEmpty()) {
            $resultId = Str::random(32);
            Cache::put($this->cacheKey($request, $resultId), $result['created']->pluck('id')->all(), now()->addDay());
        }

        return view('student.admin.import', ['result' => $result, 'token' => null, 'done' => true, 'resultId' => $resultId]);
    }

    /** Zugangszettel der neu angelegten Konten (stellt die Codes beim Herunterladen aus). */
    private function downloadCodes(Request $request, string $resultId): Response|RedirectResponse
    {
        $ids = Cache::get($this->cacheKey($request, $resultId));
        if (! is_array($ids) || $ids === []) {
            return redirect()->route('schuelerkonten.import')->with(['type' => 'warning', 'Meldung' => 'Das Ergebnis ist abgelaufen. Codes gibt es weiter in der Übersicht der Schülerkonten.']);
        }

        return app(StudentCodeSheet::class)->download(User::students()->whereIn('id', $ids)->get(), 'zugangscodes_import');
    }

    private function cacheKey(Request $request, string $id): string
    {
        return 'schuelerkonten_import:'.$request->user()->id.':'.$id;
    }

    private function rows(string $path): array
    {
        $sheets = Excel::toArray(new SchuelerImportRows, Storage::path($path));

        return $sheets[0] ?? [];
    }
}

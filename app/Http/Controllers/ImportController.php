<?php

namespace App\Http\Controllers;

use App\Exports\AufnahmeImportVorlage;
use App\Exports\SchuelerImportVorlage;
use App\Imports\AufnahmeImport;
use App\Imports\MitarbeiterImport;
use App\Imports\SchuelerImportRows;
use App\Imports\UsersImport;
use App\Imports\VereinImportRows;
use App\Services\Import\SchuelerImportService;
use App\Services\Import\VereinImportService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Exports\ElternImportVorlage;
use App\Exports\MitarbeiterImportVorlage;
use App\Exports\VereinImportAuswertung;
use App\Exports\VereinImportVorlage;
use App\Mail\ImportCredentialsMail;
use App\Model\Group;
use App\Scopes\GetGroupsScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

        // Kind-zentrierter Schüler-Import (Vorschau → Bestätigung)
        if ($type === 'schueler' && $request->hasFile('file')) {
            return $this->previewSchuelerImport($request);
        }

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
            // Nur manuelle Mitgliedschaften nicht geschützter Gruppen leeren – aus Kindern
            // abgeleitete Mitgliedschaften (is_auto_provisioned) bleiben erhalten.
            DB::table('group_user')
                ->whereIn('group_id', Group::withoutGlobalScopes()->where('protected', 0)->pluck('id'))
                ->where('is_auto_provisioned', false)
                ->delete();

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

            $this->runImport($importer, $storedPath);
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
            $this->runImport($importer, $storedPath);
            $newUsers = $importer->getNewUsers();
            $Meldung  = 'Aufnahme-Import abgeschlossen';
        } else {
            $importer = new MitarbeiterImport($sendEmail);
            $this->runImport($importer, $storedPath);
            $newUsers = $importer->getNewUsers();
            $Meldung  = 'Mitarbeiter-Import abgeschlossen';
        }
        } finally {
            if (file_exists($storedPath)) {
                unlink($storedPath);
            }
        }

        // Zugangsdaten neuer Benutzer:
        // - E-Mail-Modus ($sendEmail = true): jeder neue Benutzer hat bereits individuell
        //   seine Zugangsdaten per Mail erhalten (siehe Importer) – hier passiert nichts
        //   weiter, insbesondere wird KEINE PDF erzeugt.
        // - PDF-Modus ($sendEmail = false): neue Benutzer erhalten KEINE E-Mail. Stattdessen
        //   wird die PDF dem eingeloggten (importierenden) Benutzer zum Download angeboten
        //   UND zusätzlich per E-Mail an dessen Adresse verschickt.
        if (! $sendEmail && count($newUsers) > 0) {
            return $this->credentialsPdfResponse($request, $newUsers, $importTypeLabel);
        }

        return redirect()->to(url('users'))->with([
            'type'    => 'success',
            'Meldung' => $Meldung . (! $sendEmail ? ' – keine neuen Benutzer angelegt.' : ''),
        ]);
    }

    /**
     * PDF mit den Zugangsdaten neuer Benutzer zum Download anbieten und zusätzlich
     * per E-Mail an den importierenden Benutzer schicken (PDF-Modus der Importe).
     *
     * @param  array<int, array{name: string, email: string, password: string}>  $newUsers
     */
    private function credentialsPdfResponse(Request $request, array $newUsers, string $importTypeLabel): \Symfony\Component\HttpFoundation\Response
    {
        [$pdfContent, $filename] = $this->credentialsPdf($request, $newUsers, $importTypeLabel);

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Erzeugt das Zugangsdaten-PDF und schickt es an den importierenden Benutzer.
     *
     * @param  array<int, array{name: string, email: string, password: string}>  $newUsers
     * @return array{0: string, 1: string} PDF-Inhalt und Dateiname
     */
    private function credentialsPdf(Request $request, array $newUsers, string $importTypeLabel, ?string $notice = null): array
    {
        $pdf = Pdf::loadView('pdf.import-credentials', [
            'users'      => $newUsers,
            'importType' => $importTypeLabel,
            'notice'     => $notice,
        ]);
        $filename = 'zugangsdaten-' . now()->format('Y-m-d_H-i') . '.pdf';
        $pdfContent = $pdf->output();

        $currentUser = $request->user();
        if ($currentUser && $currentUser->email) {
            try {
                Mail::to($currentUser->email)->queue(
                    new ImportCredentialsMail($pdfContent, $filename, $importTypeLabel, count($newUsers))
                );
            } catch (\Exception $mailException) {
                \Log::error('Fehler beim Versenden der Zugangsdaten-PDF an ' . $currentUser->email . ': ' . $mailException->getMessage());
            }
        }

        return [$pdfContent, $filename];
    }

    /**
     * Führt Excel::import() aus und protokolliert eine fehlschlagende Datei-Verarbeitung
     * mit vollständigem Kontext (Exception-Klasse, Nachricht, Ort), da Fehler beim
     * Einlesen sonst nur generisch an den Nutzer durchgereicht würden.
     */
    private function runImport(object $importer, string $storedPath): void
    {
        try {
            Excel::import($importer, $storedPath);
        } catch (\Throwable $e) {
            \Log::error('Import fehlgeschlagen', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            throw $e;
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
        $group = Group::withoutGlobalScopes()->where('name', VereinImportService::GROUP_NAME)->orderBy('id')->first();

        return view('user.importVerein', [
            'memberCount' => $group?->users()->count() ?? 0,
        ]);
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

    /**
     * Schritt 1 des Vereins-Imports: Datei ablegen und Probelauf anzeigen.
     */
    public function importVerein(Request $request)
    {
        $data = $request->validate([
            'file'       => ['required', 'file', 'mimes:xlsx,xls,ods,csv', 'max:10240'],
            'send_email' => ['nullable', 'in:1,0'],
        ]);

        $path = $request->file('file')->store('imports');
        $options = [
            'removeMissing' => $request->boolean('abgleich'),
            'sendEmail'     => ($data['send_email'] ?? '1') === '1',
            'notify'        => $request->boolean('benachrichtigen'),
        ];

        $report = app(VereinImportService::class)->run($this->vereinRows($path), dryRun: true, removeMissing: $options['removeMissing']);

        return view('user.importVereinPreview', [
            'report'     => $report,
            'token'      => basename($path),
            'options'    => $options,
            'evaluation' => $this->rememberVereinResult($request, ['evaluation' => $report->evaluationRows()]),
        ]);
    }

    /**
     * Schritt 2: Vereins-Import nach Bestätigung ausführen – nur mit den
     * ausgewählten Änderungen und den Entscheidungen zu Klärungsfällen.
     */
    public function confirmVereinImport(Request $request)
    {
        $data = $request->validate([
            'token'            => ['required', 'string', 'regex:/^[A-Za-z0-9._-]+$/'],
            'send_email'       => ['nullable', 'in:1,0'],
            'selection_sent'   => ['nullable', 'in:1'],
            'selected'         => ['nullable', 'array'],
            'selected.*'       => ['string', 'max:300'],
            'decision'         => ['nullable', 'array'],
            'decision.*'       => ['string', 'max:300'],
            'decision_email'   => ['nullable', 'array'],
            'decision_email.*' => ['nullable', 'string', 'max:255'],
        ]);
        $path = 'imports/'.$data['token'];

        if (! Storage::exists($path)) {
            return redirect()->to(url('users/importVerein'))->with(['type' => 'danger', 'Meldung' => 'Importdatei nicht mehr vorhanden – bitte erneut hochladen.']);
        }

        $options = [
            'removeMissing' => $request->boolean('abgleich'),
            'sendEmail'     => ($data['send_email'] ?? '1') === '1',
            'notify'        => $request->boolean('benachrichtigen'),
        ];

        $decisions = [];
        foreach ($data['decision'] ?? [] as $row => $decision) {
            $decisions[(int) $row] = $decision === 'email'
                ? 'email:'.trim((string) ($data['decision_email'][$row] ?? ''))
                : $decision;
        }

        try {
            $report = app(VereinImportService::class)->run(
                $this->vereinRows($path),
                dryRun: false,
                removeMissing: $options['removeMissing'],
                sendEmail: $options['sendEmail'],
                selection: isset($data['selection_sent']) ? array_values($data['selected'] ?? []) : null,
                decisions: $decisions,
                notify: $options['notify'],
            );
        } finally {
            Storage::delete($path);
        }

        $stored = ['evaluation' => $report->evaluationRows()];
        if (! $options['sendEmail'] && $report->newUsers !== []) {
            [$pdfContent, $filename] = $this->credentialsPdf($request, $report->newUsers, 'Vereins-Import', VereinImportService::accessNotice());
            $stored['pdf'] = base64_encode($pdfContent);
            $stored['pdfName'] = $filename;
        }

        return view('user.importVereinPreview', [
            'report'     => $report,
            'token'      => null,
            'options'    => $options,
            'evaluation' => $this->rememberVereinResult($request, $stored),
            'hasPdf'     => isset($stored['pdf']),
        ]);
    }

    /**
     * Auswertung (nicht zugeordnet / nicht erreichbar) als Excel-Datei.
     */
    public function downloadVereinAuswertung(Request $request, string $id)
    {
        $stored = Cache::get($this->vereinResultKey($request, $id));
        abort_if($stored === null, 404, 'Auswertung nicht mehr vorhanden – bitte den Import erneut starten.');

        return Excel::download(new VereinImportAuswertung($stored['evaluation']), 'verein-import-auswertung-'.now()->format('Y-m-d_H-i').'.xlsx');
    }

    /**
     * Zugangsdaten-PDF neuer Konten (PDF-Modus) erneut herunterladen.
     */
    public function downloadVereinZugangsdaten(Request $request, string $id)
    {
        $stored = Cache::get($this->vereinResultKey($request, $id));
        abort_if(! isset($stored['pdf']), 404);

        return response(base64_decode($stored['pdf']), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$stored['pdfName'].'"',
        ]);
    }

    private function rememberVereinResult(Request $request, array $data): string
    {
        $id = (string) Str::uuid();
        Cache::put($this->vereinResultKey($request, $id), $data, now()->addDay());

        return $id;
    }

    private function vereinResultKey(Request $request, string $id): string
    {
        return 'verein-import:'.$request->user()?->id.':'.$id;
    }

    private function vereinRows(string $path): array
    {
        $sheets = Excel::toArray(new VereinImportRows, Storage::path($path));

        return $sheets[0] ?? [];
    }
}

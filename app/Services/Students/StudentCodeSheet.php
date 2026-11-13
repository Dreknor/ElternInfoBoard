<?php

namespace App\Services\Students;

use App\Model\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zettel mit Zugangsdaten für Schülerkonten (docs/konzept-schuelerkonten.md §6.2, §9.1).
 *
 * Stellt für jedes Konto einen neuen Einmal-Code aus und druckt Name, Klasse, Benutzername,
 * Code und einen QR-Code (öffnet „Code einlösen“ mit ausgefüllten Feldern), sortiert nach Klasse.
 * Der Klartext-Code existiert nur in diesem PDF; gespeichert wird nur der HMAC.
 */
class StudentCodeSheet
{
    public function __construct(private readonly StudentAccountService $accounts) {}

    /**
     * @param  Collection<int, User>  $students
     */
    public function download(Collection $students, string $filename = 'zugangscodes'): Response
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(150, 1), new SvgImageBackEnd));

        $slips = $students
            ->filter(fn (User $s) => $s->isStudent())
            ->map(function (User $student) use ($writer) {
                $code = $this->accounts->issueCode($student);
                $url = route('student.code', ['u' => $student->username, 'c' => $code]);

                return [
                    'name' => $student->name,
                    'klasse' => StudentContext::className($student) ?? '–',
                    'username' => $student->username,
                    'code' => $code,
                    'valid_until' => $student->student_code_expires_at?->format('d.m.Y'),
                    'qr' => 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($url)),
                ];
            })
            ->sortBy([['klasse', 'asc'], ['name', 'asc']])
            ->values();

        $pdf = Pdf::loadView('student.pdf.codes', [
            'slips' => $slips,
            'loginUrl' => route('student.code'),
            'appName' => app(\App\Settings\GeneralSetting::class)->app_name,
        ])->setPaper('a4');

        \App\Services\Security\SecurityLog::record('export.download', null, ['export' => 'schueler_zugangscodes', 'format' => 'pdf', 'rows' => $slips->count()]);

        return $pdf->download($filename.'_'.now()->format('Y-m-d').'.pdf');
    }
}

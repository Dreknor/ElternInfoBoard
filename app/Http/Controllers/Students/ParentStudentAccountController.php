<?php

namespace App\Http\Controllers\Students;

use App\Enums\GuardianRight;
use App\Http\Controllers\Controller;
use App\Model\Child;
use App\Services\Family\FamilyResolver;
use App\Services\Students\StudentAccountService;
use App\Settings\SchuelerSetting;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Eltern-Sicht auf das Schülerkonto des Kindes (docs/konzept-schuelerkonten.md §9, Paket S-10).
 *
 * Zustimmung/Widerspruch: Bezugspersonen mit Sorgerecht, nur bei `eltern_zustimmung`.
 * Neuer Einmal-Code: Bezugspersonen mit Verwaltungsrecht, nur bei `eltern_codes`.
 */
class ParentStudentAccountController extends Controller
{
    public function __construct(
        private readonly FamilyResolver $resolver,
        private readonly StudentAccountService $accounts,
        private readonly SchuelerSetting $settings,
    ) {}

    public function consent(Request $request, Child $child): RedirectResponse
    {
        abort_unless($this->settings->eltern_zustimmung, 404);
        abort_unless($this->resolver->hasAccessToChild($request->user(), $child, GuardianRight::Custody), 403);
        $account = $child->studentAccount;
        abort_if($account === null, 404);

        $granted = $request->boolean('zustimmung');
        $this->accounts->setConsent($account, $request->user(), $granted);

        return back()->with([
            'type' => 'success',
            'Meldung' => $granted ? 'Danke, Ihre Zustimmung ist gespeichert.' : 'Ihr Widerspruch ist gespeichert. Das Konto ist gesperrt.',
        ]);
    }

    public function code(Request $request, Child $child): RedirectResponse
    {
        abort_unless($this->settings->eltern_codes && $this->settings->login_benutzername, 404);
        abort_unless($this->resolver->hasAccessToChild($request->user(), $child, GuardianRight::Manage), 403);
        $account = $child->studentAccount;
        abort_if($account === null || $account->ucs_source === 'kelvin', 404);

        if ($account->is_active === false) {
            return back()->with(['type' => 'warning', 'Meldung' => 'Das Konto ist gesperrt. Ein Code hilft erst nach dem Entsperren.']);
        }

        $code = $this->accounts->issueCode($account);
        $writer = new Writer(new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd));
        $url = route('student.code', ['u' => $account->username, 'c' => $code]);

        // Nur für die nächste Anzeige in der Sitzung; gespeichert bleibt allein der HMAC.
        return back()->with('student_code', [
            'child' => $child->id,
            'code' => $code,
            'valid_until' => $account->fresh()->student_code_expires_at?->format('d.m.Y'),
            'qr' => 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($url)),
        ]);
    }
}

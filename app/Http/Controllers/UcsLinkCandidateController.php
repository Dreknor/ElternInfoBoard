<?php

namespace App\Http\Controllers;

use App\Model\UcsLinkCandidate;
use App\Services\Ucs\LinkCandidateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Verwaltet Bestätigungs- und Verwurfs-Aktionen für UCS-Link-Kandidaten per UI.
 *
 * Erfordert 'permission:edit settings' – ist über die gemeinsame middleware-Gruppe
 * der Settings-Routen abgesichert.
 *
 * @see resources/views/settings/tabs/_ucs-link-candidates.blade.php
 * @see docs/todos/08-initial-linking-workflow.md
 */
class UcsLinkCandidateController extends Controller
{
    /**
     * Bestätigt einen Link-Kandidaten: verknüpft das lokale Kind mit dem UCS-Account.
     */
    public function confirm(
        UcsLinkCandidate    $candidate,
        LinkCandidateService $service,
    ): RedirectResponse {
        try {
            $child = $service->confirm($candidate, auth()->user());

            return redirect()->back()->with([
                'type'    => 'success',
                'Meldung' => sprintf(
                    "Kind '%s %s' erfolgreich mit UCS-Account '%s' verknüpft.",
                    $child->first_name,
                    $child->last_name,
                    $candidate->ucs_username,
                ),
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->with([
                'type'    => 'danger',
                'Meldung' => 'Fehler beim Verknüpfen: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Verwirft einen Link-Kandidaten: verhindert erneutes Detektieren im nächsten Sync.
     */
    public function reject(
        Request             $request,
        UcsLinkCandidate    $candidate,
        LinkCandidateService $service,
    ): RedirectResponse {
        $note = (string) $request->input('note', '');

        $service->reject($candidate, auth()->user(), $note);

        return redirect()->back()->with([
            'type'    => 'success',
            'Meldung' => "Vorschlag für Kind ID {$candidate->child_id} wurde verworfen.",
        ]);
    }
}


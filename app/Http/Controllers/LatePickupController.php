<?php

namespace App\Http\Controllers;

use App\Model\LatePickup;
use App\Services\LatePickupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

/**
 * Verwaltet die Bestätigung bzw. das Verwerfen von verspäteten Abholungen.
 * Das Einsehen der Übersicht erfolgt über SchickzeitenController::indexVerwaltung()
 * (Recht "edit schickzeiten"). Für die Bestätigung/Verwerfung ist das
 * gesonderte Recht "manage late pickups" erforderlich.
 */
class LatePickupController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            'permission:manage late pickups',
        ];
    }

    public function confirm(Request $request, LatePickup $latePickup, LatePickupService $service): RedirectResponse
    {
        $request->validate([
            'comment' => ['nullable', 'string', 'max:255'],
        ]);

        $service->confirm($latePickup, $request->user(), $request->input('comment'));

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Verspätete Abholung wurde bestätigt.',
        ]);
    }

    public function reject(Request $request, LatePickup $latePickup, LatePickupService $service): RedirectResponse
    {
        $request->validate([
            'comment' => ['nullable', 'string', 'max:255'],
        ]);

        $service->reject($latePickup, $request->user(), $request->input('comment'));

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Verspätete Abholung wurde verworfen.',
        ]);
    }
}

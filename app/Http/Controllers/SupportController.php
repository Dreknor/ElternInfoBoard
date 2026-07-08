<?php

namespace App\Http\Controllers;

use App\Jobs\SendFreeScoutTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * SupportController
 *
 * Nimmt Support-Anfragen aus dem Frontend-Widget entgegen, validiert die
 * Eingaben und stellt den asynchronen Queue-Job SendFreeScoutTicket bereit.
 */
class SupportController extends Controller
{
    /**
     * POST /support/ticket
     *
     * Akzeptierte JSON-Parameter:
     *   - message    (required, string, max 5000)  – Nachrichtentext des Nutzers
     *   - screenshot (optional, string)             – Base64-kodiertes PNG der Seite
     *   - page_url   (optional, string, url, max 2048) – aktuelle Browser-URL
     *
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        Log::info('SupportController::store() aufgerufen', [
            'user_id'  => Auth::id(),
            'ip'       => $request->ip(),
            'has_msg'  => $request->has('message'),
        ]);

        Log::info('SupportController: Ticket erstellt durch '.auth()->user()->name.' ('.auth()->user()->email.')');

        $validated = $request->validate([
            'message'    => ['required', 'string', 'max:5000'],
            'screenshot' => ['nullable', 'string'],
            'page_url'   => ['nullable', 'string', 'max:2048'],
        ]);

        /** @var \App\Model\User $user */
        $user = Auth::user();

        // Primäre Rolle des eingeloggten Nutzers ermitteln
        $role = $user->roles->first()?->name ?? null;

        $payload = [
            'subject'      => 'Support-Anfrage von ' . $user->name,
            'message'      => $validated['message'],
            'screenshot'   => $validated['screenshot'] ?? null,
            'page_url'     => $validated['page_url'] ?? $request->header('Referer'),
            'user_name'    => $user->name,
            'user_email'   => $user->email,
            'user_role'    => $role,
        ];

        try {
            SendFreeScoutTicket::dispatchTicket($payload);
        } catch (\Throwable $e) {
            // Tritt nur bei FREESCOUT_QUEUE_SYNC=true auf, wenn der Job synchron
            // fehlschlägt (z. B. FreeScout-API nicht erreichbar).
            Log::error('SupportController: Ticket konnte nicht erstellt werden.', [
                'error'   => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Das Ticket konnte leider nicht übermittelt werden. '
                           . 'Bitte versuche es später erneut oder kontaktiere uns direkt.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message' => 'Deine Anfrage wurde erfolgreich übermittelt.',
        ]);
    }
}



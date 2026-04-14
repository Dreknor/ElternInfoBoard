<?php

namespace App\Http\Controllers;

use App\Model\Conversation;
use App\Model\MessageReport;
use App\Model\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessengerAdminController extends Controller
{
    /**
     * Übersicht aller offenen Meldungen.
     */
    public function reports(): View
    {
        $reports = MessageReport::with(['message.sender', 'message.conversation', 'reporter'])
            ->whereNull('resolved_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        $resolvedCount = MessageReport::whereNotNull('resolved_at')->count();

        return view('messenger.admin.reports', compact('reports', 'resolvedCount'));
    }

    /**
     * Gemeldete Nachricht als erledigt markieren.
     */
    public function resolveReport(Request $request, MessageReport $report): RedirectResponse
    {
        $report->update([
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
        ]);

        return back()->with('Meldung', 'Meldung als erledigt markiert.')->with('type', 'success');
    }

    /**
     * Nutzer in allen Gruppen-Chats stumm schalten.
     */
    public function muteUser(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'hours' => ['required', 'integer', 'min:1', 'max:720'],
        ]);

        $mutedUntil = now()->addHours($request->hours);

        // Alle Gruppen-Konversationen des Users stumm schalten
        Conversation::where('type', 'group')
            ->forUser($user->id)
            ->each(function (Conversation $conv) use ($user, $mutedUntil) {
                $conv->users()->updateExistingPivot($user->id, ['muted_until' => $mutedUntil]);
            });

        return back()->with('Meldung', "{$user->name} wurde für {$request->hours} Stunden stummgeschaltet.")->with('type', 'success');
    }
}


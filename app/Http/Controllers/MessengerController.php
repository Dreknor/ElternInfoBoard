<?php

namespace App\Http\Controllers;

use App\Model\Conversation;
use App\Model\Group;
use App\Model\Message;
use App\Model\MessageReport;
use App\Model\User;
use App\Notifications\MessengerPushNotification;
use App\Settings\MessengerSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
class MessengerController extends Controller
{
    public function __construct(private readonly MessengerSetting $settings) {}

    /**
     * Übersicht aller Konversationen des eingeloggten Users.
     */
    public function index(): View
    {
        $user = auth()->user();

        $conversations = Conversation::forUser($user->id)
            ->where('is_active', true)
            ->with(['users', 'latestMessage.sender', 'group'])
            ->get()
            ->sortByDesc(fn ($c) => $c->latestMessage?->created_at)
            ->values();

        // Ungelesene Zähler pro Konversation
        $conversations->each(function (Conversation $conv) use ($user) {
            $conv->unread_count = $conv->unreadCountFor($user->id);
            $conv->display_name = $conv->displayNameFor($user->id);
        });

        return view('messenger.index', compact('conversations'));
    }

    /**
     * Eine Konversation öffnen.
     */
    public function show(Conversation $conversation): View
    {
        Gate::authorize('view', $conversation);

        $user = auth()->user();

        $messages = $conversation->messagesVisibleTo($user->id)
            ->with(['sender', 'replyTo.sender'])
            ->latest()
            ->paginate(50);

        // Als gelesen markieren
        $conversation->users()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        $display_name = $conversation->displayNameFor($user->id);

        return view('messenger.show', compact('conversation', 'messages', 'display_name'));
    }

    /**
     * Neue Nachricht in einer Konversation senden.
     */
    public function sendMessage(Request $request, Conversation $conversation): RedirectResponse
    {
        Gate::authorize('sendMessage', $conversation);

        // Rate-Limiting: max. 30 Nachrichten/Minute
        $key = 'messenger_send_' . auth()->id();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return back()->withErrors(['body' => 'Zu viele Nachrichten. Bitte warte einen Moment.']);
        }
        RateLimiter::hit($key, 60);

        $request->validate([
            'body'        => ['required', 'string', 'max:' . $this->settings->max_message_length],
            'reply_to_id' => ['nullable', 'integer', 'exists:messages,id'],
            'attachment'  => ['nullable', 'file', 'max:' . ($this->settings->max_file_size_mb * 1024)],
        ]);

        $type = 'text';
        if ($request->hasFile('attachment')) {
            $mime = $request->file('attachment')->getMimeType();
            $type = str_starts_with($mime, 'image/') ? 'image' : 'file';
        }

        $message = $conversation->messages()->create([
            'sender_id'   => auth()->id(),
            'body'        => $request->body,
            'type'        => $type,
            'reply_to_id' => $request->reply_to_id,
        ]);

        // Anhang hochladen
        if ($request->hasFile('attachment') && $this->settings->allow_file_uploads) {
            $message->addMediaFromRequest('attachment')
                ->toMediaCollection('message-attachments');
        }

        // Andere Teilnehmer benachrichtigen
        $this->notifyParticipants($conversation, $message);

        return redirect()->route('messenger.show', $conversation)
            ->with('Meldung', 'Nachricht gesendet.')
            ->with('type', 'success');
    }

    /**
     * User-Suche für Direktnachrichten (AJAX / JSON).
     * Gibt max. 10 User aus gemeinsamen Gruppen zurück, die auf den Suchbegriff passen.
     * Nutzt direkte DB-Abfragen, um GetGroupsScope zu umgehen.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $user = auth()->user();

        // Eigene Gruppen-IDs ohne GetGroupsScope
        $myGroupIds = DB::table('group_user')
            ->where('user_id', $user->id)
            ->pluck('group_id');

        if ($myGroupIds->isEmpty()) {
            return response()->json([]);
        }

        // User-IDs aus denselben Gruppen (ohne sich selbst)
        $userIds = DB::table('group_user')
            ->whereIn('group_id', $myGroupIds)
            ->where('user_id', '!=', $user->id)
            ->pluck('user_id')
            ->unique();

        $users = User::whereIn('id', $userIds)
            ->where('is_active', true)
            ->where('messenger_discoverable', true)
            ->where('name', 'like', '%' . $q . '%')
            ->orderBy('name')
            ->select('id', 'name')
            ->limit(10)
            ->get();

        return response()->json($users);
    }

    /**
     * Direktnachricht an einen User starten (oder bestehende öffnen).
     */
    public function startDirect(Request $request, User $targetUser): RedirectResponse
    {
        $user = auth()->user();

        // Prüfen ob Direktnachrichten erlaubt sind
        if (! $this->settings->allow_direct_messages) {
            return back()->with('Meldung', 'Direktnachrichten sind deaktiviert.')->with('type', 'danger');
        }

        // Nur Mitglieder der eigenen Gruppen anschreiben (ohne GetGroupsScope)
        $myGroupIds = DB::table('group_user')->where('user_id', $user->id)->pluck('group_id');
        $targetGroupIds = DB::table('group_user')->where('user_id', $targetUser->id)->pluck('group_id');
        $sharedGroups = $myGroupIds->intersect($targetGroupIds);

        if ($sharedGroups->isEmpty()) {
            return back()->with('Meldung', 'Du kannst nur Mitglieder deiner Gruppen anschreiben.')->with('type', 'danger');
        }

        // Bestehende Direktkonversation suchen
        $existing = Conversation::where('type', 'direct')
            ->forUser($user->id)
            ->whereHas('users', fn ($q) => $q->where('users.id', $targetUser->id))
            ->first();

        if ($existing) {
            return redirect()->route('messenger.show', $existing);
        }

        // Neue Konversation anlegen
        $conversation = Conversation::create([
            'type'       => 'direct',
            'created_by' => $user->id,
        ]);

        // syncWithoutDetaching verhindert Duplicate-Key bei gleichzeitigen Requests
        $conversation->users()->syncWithoutDetaching([$user->id, $targetUser->id]);

        return redirect()->route('messenger.show', $conversation);
    }

    /**
     * Nachricht bearbeiten (nur innerhalb von 15 Minuten).
     */
    public function editMessage(Request $request, Message $message): RedirectResponse
    {
        if (! $message->isEditableBy(auth()->user())) {
            abort(403, 'Diese Nachricht kann nicht mehr bearbeitet werden.');
        }

        $request->validate([
            'body' => ['required', 'string', 'max:' . $this->settings->max_message_length],
        ]);

        $message->update([
            'body'      => $request->body,
            'edited_at' => now(),
        ]);

        return back()->with('Meldung', 'Nachricht aktualisiert.')->with('type', 'success');
    }

    /**
     * Nachricht löschen (Soft-Delete).
     */
    public function deleteMessage(Message $message): RedirectResponse
    {
        if (! $message->isDeletableBy(auth()->user())) {
            abort(403, 'Keine Berechtigung zum Löschen dieser Nachricht.');
        }

        $conversationId = $message->conversation_id;
        $message->delete();

        return redirect()->route('messenger.show', $conversationId)
            ->with('Meldung', 'Nachricht gelöscht.')
            ->with('type', 'success');
    }

    /**
     * Nachricht melden.
     */
    public function reportMessage(Request $request, Message $message): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        // Prüfen ob der Meldende Teilnehmer der Konversation ist
        $conversation = $message->conversation()->with('users')->first();
        if (! $conversation || ! $conversation->users->contains('id', auth()->id())) {
            abort(403, 'Keine Berechtigung, diese Nachricht zu melden.');
        }

        // Eigene Nachrichten nicht meldbar
        if ($message->sender_id === auth()->id()) {
            return back()
                ->with('Meldung', 'Du kannst deine eigenen Nachrichten nicht melden.')
                ->with('type', 'warning');
        }

        // Doppelte Meldung verhindern
        $alreadyReported = MessageReport::where('message_id', $message->id)
            ->where('reporter_id', auth()->id())
            ->whereNull('resolved_at')
            ->exists();

        if ($alreadyReported) {
            return back()
                ->with('Meldung', 'Du hast diese Nachricht bereits gemeldet.')
                ->with('type', 'warning');
        }

        MessageReport::create([
            'message_id'  => $message->id,
            'reporter_id' => auth()->id(),
            'reason'      => $request->reason,
        ]);

        return back()
            ->with('Meldung', 'Nachricht wurde gemeldet. Ein Moderator wird sie prüfen.')
            ->with('type', 'success');
    }

    /**
     * Konversation stumm schalten / Stummschaltung aufheben.
     */
    public function toggleMute(Request $request, Conversation $conversation): RedirectResponse
    {
        Gate::authorize('view', $conversation);

        $pivot = $conversation->users->where('id', auth()->id())->first()?->pivot;

        if ($pivot && $pivot->muted_until && now()->lessThan($pivot->muted_until)) {
            // Stummschaltung aufheben
            $conversation->users()->updateExistingPivot(auth()->id(), ['muted_until' => null]);
            $status = 'Stummschaltung aufgehoben.';
        } else {
            // 24 Stunden stumm schalten
            $conversation->users()->updateExistingPivot(auth()->id(), [
                'muted_until' => now()->addHours(24),
            ]);
            $status = 'Konversation für 24 Stunden stummgeschaltet.';
        }

        return back()->with('Meldung', $status)->with('type', 'success');
    }

    // ── Hilfsmethoden ────────────────────────────────────────────

    private function notifyParticipants(Conversation $conversation, Message $message): void
    {
        $sender = auth()->user();

        $participants = $conversation->users
            ->where('id', '!=', $sender->id)
            ->filter(function ($user) {
                $pivot = $user->pivot;
                return ! ($pivot->muted_until && now()->lessThan($pivot->muted_until));
            });

        if ($participants->isEmpty()) {
            return;
        }

        $title   = 'Neue Nachricht: ' . $conversation->displayNameFor($sender->id);
        $snippet = mb_substr($message->body, 0, 80) . (mb_strlen($message->body) > 80 ? '…' : '');
        $url     = route('messenger.show', $conversation);

        // In-App-Benachrichtigung (Glocke im Menü)
        $conversation->notify(
            $participants,
            $title,
            "{$sender->name}: {$snippet}",
            false,
            $url,
            'messenger',
            'fas fa-comments'
        );

        // WebPush für User mit aktiven Push-Subscriptions
        foreach ($participants as $participant) {
            try {
                if ($participant->pushSubscriptions()->exists()) {
                    $participant->notify(
                        new MessengerPushNotification($title, "{$sender->name}: {$snippet}", $url)
                    );
                }
            } catch (\Exception $e) {
                \Log::warning("Messenger WebPush fehlgeschlagen für User {$participant->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Dateianhang einer Nachricht sicher ausliefern.
     * Bilder werden inline angezeigt, andere Dateien als Download.
     */
    public function serveAttachment(Message $message): BinaryFileResponse
    {
        // Sichtbarkeit prüfen: Mitglied + Nachricht nach Beitritt verfasst
        if (! $message->isVisibleTo(auth()->user())) {
            abort(403, 'Kein Zugriff auf diesen Anhang.');
        }

        $media = $message->getFirstMedia('message-attachments');
        if (! $media) {
            abort(404, 'Anhang nicht gefunden.');
        }

        $filePath = $media->getPath();
        if (! file_exists($filePath)) {
            abort(404, 'Datei nicht gefunden.');
        }

        $isImage      = str_starts_with($media->mime_type, 'image/');
        $disposition  = $isImage ? 'inline' : 'attachment';

        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', $media->mime_type);
        $response->headers->set(
            'Content-Disposition',
            $disposition . '; filename="' . $media->file_name . '"'
        );
        // Caching verhindern (Inhalte sind nutzerspezifisch)
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}


<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Model\Conversation;
use App\Model\Message;
use App\Services\Students\StudentMessenger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Chat der Schüler mit Lehrkräften (docs/konzept-schuelerkonten.md §4, Paket S-14).
 * Web (`student.messenger.*`) und App-API (`api.v1.student.messenger.*`).
 */
class StudentMessengerController extends Controller
{
    /** Routen liegen hinter der Middleware `student.messenger` (404, wenn ausgeschaltet). */
    public function __construct(private readonly StudentMessenger $messenger) {}

    // ── Web ────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $student = $request->user();

        return view('student.messenger.index', [
            'conversations' => $this->messenger->conversationsFor($student),
            'teachers' => $this->messenger->teachersFor($student),
        ]);
    }

    public function show(Request $request, int $conversation): View
    {
        $student = $request->user();
        $model = $this->messenger->findFor($student, $conversation);
        abort_if($model === null, 404);

        return view('student.messenger.show', [
            'conversation' => $model,
            'teacher' => $this->messenger->teacherOf($model, $student),
            'messages' => $this->messenger->messages($student, $model),
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $data = $request->validate(['teacher_id' => ['required', 'integer']]);

        try {
            $conversation = $this->messenger->start($request->user(), (int) $data['teacher_id']);
        } catch (\DomainException $e) {
            return back()->with(['type' => 'danger', 'Meldung' => $e->getMessage()]);
        }

        return redirect()->route('student.messenger.show', $conversation->id);
    }

    public function send(Request $request, int $conversation): RedirectResponse
    {
        $student = $request->user();
        $model = $this->messenger->findFor($student, $conversation);
        abort_if($model === null, 404);
        $data = $request->validate(['body' => ['required', 'string', 'max:'.StudentMessenger::MAX_LENGTH]]);

        if ($this->tooMany($student->id)) {
            return back()->withErrors(['body' => 'Zu viele Nachrichten. Bitte warte einen Moment.']);
        }

        try {
            $this->messenger->send($student, $model, $data['body']);
        } catch (\DomainException $e) {
            return back()->withInput()->with(['type' => 'danger', 'Meldung' => $e->getMessage()]);
        }

        return redirect()->route('student.messenger.show', $model->id);
    }

    // ── App-API ────────────────────────────────────────────────────────────

    public function apiIndex(Request $request): JsonResponse
    {
        $student = $request->user();

        return response()->json([
            'data' => $this->messenger->conversationsFor($student)->map(fn (Conversation $c) => [
                'id' => $c->id,
                'lehrkraft' => $c->teacher?->name,
                'aktiv' => (bool) $c->is_active,
                'ungelesen' => $c->unread_count,
                'letzte_nachricht' => $c->latestMessage ? [
                    'text' => $c->latestMessage->trashed() ? null : mb_strimwidth((string) $c->latestMessage->body, 0, 120, '…'),
                    'von_mir' => (int) $c->latestMessage->sender_id === $student->id,
                    'am' => $c->latestMessage->created_at?->toIso8601String(),
                ] : null,
            ])->values(),
            'meta' => ['lehrkraefte' => $this->messenger->teachersFor($student)->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values()],
        ]);
    }

    public function apiShow(Request $request, int $conversation): JsonResponse
    {
        $student = $request->user();
        $model = $this->messenger->findFor($student, $conversation);
        abort_if($model === null, 404);

        return response()->json([
            'data' => $this->messenger->messages($student, $model)->map(fn (Message $m) => $this->format($m, $student->id))->values(),
            'meta' => ['aktiv' => (bool) $model->is_active, 'lehrkraft' => $this->messenger->teacherOf($model, $student)?->name],
        ]);
    }

    public function apiStart(Request $request): JsonResponse
    {
        $data = $request->validate(['teacher_id' => ['required', 'integer']]);

        try {
            $conversation = $this->messenger->start($request->user(), (int) $data['teacher_id']);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => ['id' => $conversation->id]], 201);
    }

    public function apiSend(Request $request, int $conversation): JsonResponse
    {
        $student = $request->user();
        $model = $this->messenger->findFor($student, $conversation);
        abort_if($model === null, 404);
        $data = $request->validate(['body' => ['required', 'string', 'max:'.StudentMessenger::MAX_LENGTH]]);

        if ($this->tooMany($student->id)) {
            return response()->json(['message' => 'Zu viele Nachrichten. Bitte warte einen Moment.'], 429);
        }

        try {
            $message = $this->messenger->send($student, $model, $data['body']);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->format($message->load('sender:id,name'), $student->id)], 201);
    }

    private function format(Message $m, int $me): array
    {
        return [
            'id' => $m->id,
            'text' => $m->trashed() ? null : $m->body,
            'von_mir' => (int) $m->sender_id === $me,
            'absender' => $m->sender?->name,
            'am' => $m->created_at?->toIso8601String(),
        ];
    }

    private function tooMany(int $userId): bool
    {
        $key = 'student_chat_'.$userId;
        if (RateLimiter::tooManyAttempts($key, 20)) {
            return true;
        }
        RateLimiter::hit($key, 60);

        return false;
    }
}

<?php

namespace App\Services\Students;

use App\Model\Conversation;
use App\Model\Message;
use App\Model\User;
use App\Services\Notifications\PushDispatcher;
use App\Settings\SchuelerSetting;
use App\Support\StudentAccounts;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Messenger zwischen Schülern und Lehrkräften (docs/konzept-schuelerkonten.md §4, Paket S-14).
 *
 * Nur mit aktivem Modul und der Einstellung `messenger_lehrkraefte`. Erlaubt sind ausschließlich
 * Direktchats (Typ `student`) zwischen einem Schülerkonto und einer Lehrkraft seiner Gruppen.
 * Lehrkraft heißt: Mitglied einer Klasse/Gruppe des Kindes, aktiv, mit den Rechten
 * „use messenger“ und „create student posts“. Keine Gruppenchats, keine Eltern, keine anderen
 * Schüler, keine Anhänge. Die Lehrkraft kann den Chat beenden; danach kann der Schüler ihn
 * nicht neu starten.
 */
class StudentMessenger
{
    public const TYPE = 'student';

    public const MAX_LENGTH = 2000;

    public static function enabled(): bool
    {
        if (! StudentAccounts::enabled()) {
            return false;
        }

        try {
            return (bool) app(SchuelerSetting::class)->messenger_lehrkraefte;
        } catch (\Throwable) {
            return false;
        }
    }

    /** Lehrkräfte, denen dieser Schüler schreiben darf. */
    public function teachersFor(User $student): Collection
    {
        $groupIds = StudentContext::groupIds($student);
        if ($groupIds === []) {
            return collect();
        }

        $userIds = DB::table('group_user')->whereIn('group_id', $groupIds)->pluck('user_id')->unique();

        return User::query()
            ->whereIn('id', $userIds)
            ->where('is_active', true)
            ->permission('use messenger')
            ->get(['id', 'name'])
            ->filter(fn (User $u) => $u->can(StudentAccounts::PERMISSION_CREATE_POSTS))
            ->sortBy('name')
            ->values();
    }

    public function isTeacherFor(User $student, int $teacherId): bool
    {
        return $this->teachersFor($student)->contains('id', $teacherId);
    }

    /** Chats des Schülers, neueste zuerst. */
    public function conversationsFor(User $student): Collection
    {
        $ids = DB::table('conversation_user')->where('user_id', $student->id)->pluck('conversation_id');

        return Conversation::query()
            ->whereIn('id', $ids)
            ->where('type', self::TYPE)
            ->with('latestMessage')
            ->get()
            ->map(function (Conversation $c) use ($student) {
                $c->teacher = $this->teacherOf($c, $student);
                $c->unread_count = $this->unreadFor($c, $student);

                return $c;
            })
            ->sortByDesc(fn ($c) => $c->latestMessage?->created_at ?? $c->created_at)
            ->values();
    }

    public function findFor(User $student, int $conversationId): ?Conversation
    {
        $member = DB::table('conversation_user')->where('conversation_id', $conversationId)->where('user_id', $student->id)->exists();

        return $member ? Conversation::query()->where('type', self::TYPE)->find($conversationId) : null;
    }

    /** Chat mit einer Lehrkraft öffnen oder anlegen. */
    public function start(User $student, int $teacherId): Conversation
    {
        if (! $this->isTeacherFor($student, $teacherId)) {
            throw new \DomainException('Du kannst nur Lehrkräften deiner Klasse oder Gruppen schreiben.');
        }

        $existing = Conversation::withTrashed()
            ->where('type', self::TYPE)
            ->whereIn('id', DB::table('conversation_user')->where('user_id', $student->id)->select('conversation_id'))
            ->whereIn('id', DB::table('conversation_user')->where('user_id', $teacherId)->select('conversation_id'))
            ->first();

        if ($existing) {
            if ($existing->trashed() || ! $existing->is_active) {
                throw new \DomainException('Die Lehrkraft hat diesen Chat beendet.');
            }

            return $existing;
        }

        $klasse = StudentContext::className($student);
        $conversation = Conversation::create([
            'type' => self::TYPE,
            'created_by' => $student->id,
            'title' => $student->name.($klasse ? " ({$klasse})" : '').' · Schülerchat',
            'is_active' => true,
        ]);
        $conversation->users()->syncWithoutDetaching([
            $student->id => ['joined_at' => now()],
            $teacherId => ['joined_at' => now()],
        ]);

        StudentAccountLog::info('Schülerkonto: Chat mit Lehrkraft begonnen', $student, ['conversation_id' => $conversation->id, 'lehrkraft_id' => $teacherId]);

        return $conversation;
    }

    /** Nachricht des Schülers senden. */
    public function send(User $student, Conversation $conversation, string $body): Message
    {
        $body = trim($body);
        if ($body === '' || mb_strlen($body) > self::MAX_LENGTH) {
            throw new \DomainException('Die Nachricht ist leer oder zu lang.');
        }
        if (! $conversation->is_active) {
            throw new \DomainException('Die Lehrkraft hat diesen Chat beendet.');
        }

        $teacher = $this->teacherOf($conversation, $student);
        if ($teacher === null || ! $this->isTeacherFor($student, $teacher->id)) {
            throw new \DomainException('Diese Lehrkraft kannst du nicht mehr anschreiben.');
        }

        $message = $conversation->messages()->create([
            'sender_id' => $student->id,
            'body' => $body,
            'type' => 'text',
        ]);
        DB::table('conversation_user')->where('conversation_id', $conversation->id)->where('user_id', $student->id)->update(['last_read_at' => now()]);

        $conversation->load('users');
        $conversation->notify(
            $conversation->users->where('id', '!=', $student->id),
            'Neue Nachricht: '.$conversation->title,
            $student->name.': '.mb_strimwidth($body, 0, 80, '…'),
            false,
            route('messenger.show', $conversation),
            'messenger',
            'fas fa-comments',
            true
        );

        return $message;
    }

    /** Nachrichten (älteste zuerst) und als gelesen markieren. */
    public function messages(User $student, Conversation $conversation, int $limit = 100): Collection
    {
        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->orderByDesc('created_at')->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        DB::table('conversation_user')->where('conversation_id', $conversation->id)->where('user_id', $student->id)->update(['last_read_at' => now()]);

        return $messages;
    }

    /** Lehrkraft beendet einen Schülerchat. */
    public function close(User $teacher, Conversation $conversation): void
    {
        if ($conversation->type !== self::TYPE || ! $conversation->users->contains('id', $teacher->id)) {
            abort(404);
        }

        $conversation->forceFill(['is_active' => false])->save();
        StudentAccountLog::info('Schülerchat von Lehrkraft beendet', null, ['conversation_id' => $conversation->id, 'lehrkraft_id' => $teacher->id]);
    }

    /** Push an den Schüler, wenn die Lehrkraft antwortet (nur Titel). */
    public static function notifyStudent(Conversation $conversation, User $sender): void
    {
        if ($conversation->type !== self::TYPE || ! self::enabled()) {
            return;
        }

        $ids = User::students()->where('is_active', true)
            ->whereIn('id', DB::table('conversation_user')->where('conversation_id', $conversation->id)->pluck('user_id'))
            ->whereKeyNot($sender->id)
            ->pluck('id')->map(fn ($id) => (int) $id)->all();

        PushDispatcher::dispatch($ids, 'Neue Nachricht', $sender->name, route('student.messenger.show', $conversation->id), 'Nachrichten');
    }

    /** Alle Schülerchats eines Kontos löschen (beim Löschen des Kontos). */
    public static function deleteFor(int $studentId): void
    {
        $ids = Conversation::withTrashed()->where('type', self::TYPE)
            ->whereIn('id', DB::table('conversation_user')->where('user_id', $studentId)->select('conversation_id'))
            ->pluck('id');

        foreach (Conversation::withTrashed()->whereIn('id', $ids)->get() as $conversation) {
            $conversation->messages()->withTrashed()->get()->each(fn (Message $m) => $m->forceDelete());
            DB::table('conversation_user')->where('conversation_id', $conversation->id)->delete();
            $conversation->forceDelete();
        }
    }

    public function teacherOf(Conversation $conversation, User $student): ?User
    {
        $id = DB::table('conversation_user')->where('conversation_id', $conversation->id)->where('user_id', '!=', $student->id)->value('user_id');

        return $id ? User::query()->select(['id', 'name'])->find($id) : null;
    }

    private function unreadFor(Conversation $conversation, User $student): int
    {
        $lastRead = DB::table('conversation_user')->where('conversation_id', $conversation->id)->where('user_id', $student->id)->value('last_read_at');

        return $conversation->messages()
            ->where('sender_id', '!=', $student->id)
            ->when($lastRead, fn ($q) => $q->where('created_at', '>', $lastRead))
            ->count();
    }
}

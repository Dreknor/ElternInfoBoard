<?php

namespace App\Services\App;

use App\Http\Resources\V1\ChildResource;
use App\Model\ChildCheckIn;
use App\Model\User;
use Illuminate\Support\Facades\DB;

/**
 * Offene Aufgaben der Familie (B-25): Lesebestätigungen, Pflicht-Rückmeldungen, Anwesenheitsabfragen.
 * Grundlage für Start-Badge, „Zu erledigen“ und Erinnerungen.
 */
class TodoService
{
    public function forUser(User $user): array
    {
        $family = Family::userIds($user);
        $todos = [];

        $posts = PostQuery::current($user)
            ->with('rueckmeldung')
            ->where(function ($q) {
                $q->where('read_receipt', true)->orWhereHas('rueckmeldung', fn ($r) => $r->where('pflicht', true));
            })
            ->get(['posts.id', 'posts.header', 'posts.read_receipt', 'posts.read_receipt_deadline']);

        $postIds = $posts->pluck('id');
        $confirmed = DB::table('read_receipts')->whereIn('post_id', $postIds)->whereIn('user_id', $family)
            ->whereNotNull('confirmed_at')->pluck('post_id')->flip();
        $answered = DB::table('users_rueckmeldungen')->whereIn('post_id', $postIds)->whereIn('users_id', $family)
            ->whereNull('deleted_at')->pluck('post_id')->flip();

        foreach ($posts as $post) {
            if ($post->read_receipt && ! $confirmed->has($post->id)) {
                $todos[] = [
                    'type' => 'read_receipt',
                    'title' => $post->header,
                    'subtitle' => 'Lesebestätigung ausstehend',
                    'deadline' => $post->read_receipt_deadline?->toDateString(),
                    'target' => ['type' => 'post', 'id' => $post->id],
                ];
            }
            $r = $post->rueckmeldung;
            if ($r && $r->pflicht && FeedbackService::isOpen($r) && ! $answered->has($post->id)
                && in_array($r->type, ['email', 'text', 'abfrage', 'bild'], true)) {
                $todos[] = [
                    'type' => 'feedback',
                    'title' => $post->header,
                    'subtitle' => $r->type === 'abfrage' ? 'Abfrage ausfüllen' : 'Rückmeldung geben',
                    'deadline' => $r->ende?->toDateString(),
                    'target' => ['type' => 'post', 'id' => $post->id],
                ];
            }
        }

        $careChildren = Family::children($user)->filter(fn ($c) => ChildResource::inCare($c))->keyBy('id');
        if ($careChildren->isNotEmpty()) {
            $open = ChildCheckIn::query()
                ->whereIn('child_id', $careChildren->keys())
                ->whereDate('date', '>', today())
                ->whereNull('should_be')
                ->where(fn ($q) => $q->whereNull('lock_at')->orWhereDate('lock_at', '>=', today()))
                ->orderBy('date')
                ->get(['id', 'child_id', 'date', 'lock_at'])
                ->groupBy('child_id');

            foreach ($open as $childId => $checkIns) {
                $child = $careChildren->get($childId);
                $todos[] = [
                    'type' => 'attendance',
                    'title' => 'Anwesenheit '.$child->first_name,
                    'subtitle' => $checkIns->count().' '.($checkIns->count() === 1 ? 'Tag' : 'Tage').' offen ab '.$checkIns->first()->date->format('d.m.'),
                    'deadline' => $checkIns->first()->lock_at?->toDateString(),
                    'target' => ['type' => 'child', 'id' => (int) $childId],
                ];
            }
        }

        usort($todos, fn ($a, $b) => strcmp($a['deadline'] ?? '9999', $b['deadline'] ?? '9999'));

        return $todos;
    }
}

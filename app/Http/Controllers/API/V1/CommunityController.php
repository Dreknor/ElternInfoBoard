<?php

namespace App\Http\Controllers\API\V1;

use App\Model\Discussion;
use App\Model\ElternratEvent;
use App\Model\ElternratTask;
use App\Model\EventAttendee;
use App\Model\Site;
use App\Model\SiteBlockFiles;
use App\Model\SiteBlockImages;
use App\Model\SiteBlockText;
use App\Services\App\HtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Infoseiten der Schule (B-72) und Elternrat (B-73).
 *
 * @group App: Seiten & Elternrat
 */
class CommunityController extends ApiController
{
    /** Aktive Infoseiten der eigenen Gruppen. */
    public function sites(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->visibleSites($request)->get(['sites.id', 'sites.name', 'sites.updated_at'])
            ->map(fn (Site $s) => ['id' => $s->id, 'name' => $s->name, 'updated_at' => $s->updated_at?->toIso8601String()])]);
    }

    /** Infoseite mit Blöcken (Text als bereinigtes HTML, Bilder, Dateien). */
    public function site(Request $request, int $site): JsonResponse
    {
        $site = $this->visibleSites($request)->findOrFail($site);
        $blocks = $site->blocks()->orderBy('position')->with('block')->get();

        return response()->json(['data' => [
            'id' => $site->id,
            'name' => $site->name,
            'blocks' => $blocks->map(function ($b) {
                $content = $b->block;
                $media = fn ($m) => [
                    'uuid' => $m->uuid,
                    'name' => $m->name ?: $m->file_name,
                    'mime_type' => $m->mime_type,
                    'size' => $m->size,
                    'url' => route('api.files.download', ['media_uuid' => $m->uuid]),
                ];

                return [
                    'id' => $b->id,
                    'title' => $b->title,
                    'type' => match (true) {
                        $content instanceof SiteBlockText => 'text',
                        $content instanceof SiteBlockImages => 'images',
                        $content instanceof SiteBlockFiles => 'files',
                        default => 'unknown',
                    },
                    'html' => $content instanceof SiteBlockText ? HtmlSanitizer::clean((string) $content->content) : null,
                    'media' => ($content instanceof SiteBlockImages || $content instanceof SiteBlockFiles)
                        ? $content->getMedia()->map($media)->values() : [],
                ];
            })->values(),
        ]]);
    }

    /** Elternrat: Themen (paginiert), Termine mit eigener Zusage, eigene Aufgaben. */
    public function elternrat(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('view elternrat'), 403);
        $userId = $request->user()->id;

        $themen = Discussion::query()->with('author:id,name')->withCount('comments')
            ->orderByDesc('sticky')->orderByDesc('updated_at')->limit(30)->get();

        $events = ElternratEvent::query()->where('end_time', '>=', now()->startOfDay())
            ->with(['attendees' => fn ($q) => $q->where('user_id', $userId)])
            ->orderBy('start_time')->limit(20)->get();

        $tasks = ElternratTask::query()->where('assigned_to', $userId)->where('status', '!=', 'completed')
            ->orderBy('due_date')->get();

        return response()->json(['data' => [
            'themen' => $themen->map(fn (Discussion $d) => [
                'id' => $d->id,
                'header' => $d->header,
                'excerpt' => mb_strimwidth(trim(strip_tags((string) $d->text)), 0, 200, '…'),
                'author' => $d->author?->name,
                'sticky' => (bool) $d->sticky,
                'comments_count' => $d->comments_count,
                'updated_at' => $d->updated_at?->toIso8601String(),
            ]),
            'termine' => $events->map(fn (ElternratEvent $e) => [
                'id' => $e->id,
                'title' => $e->title,
                'description' => $e->description,
                'location' => $e->location,
                'start' => $e->start_time?->toIso8601String(),
                'end' => $e->end_time?->toIso8601String(),
                'my_status' => $e->attendees->first()?->status,
                'accepted' => $e->acceptedCount(),
            ]),
            'aufgaben' => $tasks->map(fn (ElternratTask $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'status' => $t->status,
                'priority' => $t->priority,
                'due_date' => $t->due_date?->toDateString(),
            ]),
        ]]);
    }

    /** Thema mit Kommentaren. */
    public function discussion(Request $request, Discussion $discussion): JsonResponse
    {
        abort_unless($request->user()->can('view elternrat'), 403);
        $discussion->load(['author:id,name', 'comments.creator']);

        return response()->json(['data' => [
            'id' => $discussion->id,
            'header' => $discussion->header,
            'html' => HtmlSanitizer::clean((string) $discussion->text),
            'author' => $discussion->author?->name,
            'updated_at' => $discussion->updated_at?->toIso8601String(),
            'comments' => $discussion->comments->sortBy('created_at')->values()->map(fn ($c) => [
                'id' => $c->id,
                'body' => $c->body,
                'author' => $c->creator?->name ?? 'Unbekannt',
                'own' => (int) $c->creator_id === $request->user()->id,
                'created_at' => $c->created_at?->toIso8601String(),
            ]),
        ]]);
    }

    public function commentDiscussion(Request $request, Discussion $discussion): JsonResponse
    {
        abort_unless($request->user()->can('view elternrat'), 403);
        $request->validate(['body' => 'required|string|max:5000']);
        $discussion->comment(['body' => $request->body], $request->user());

        return response()->json(['message' => 'Kommentar gespeichert.'], 201);
    }

    /**
     * Zu- oder Absage zu einem Elternrats-Termin.
     *
     * @bodyParam status string required accepted|declined|maybe
     */
    public function attendance(Request $request, ElternratEvent $event): JsonResponse
    {
        abort_unless($request->user()->can('view elternrat'), 403);
        $data = $request->validate(['status' => 'required|in:accepted,declined,maybe', 'comment' => 'nullable|string|max:500']);
        EventAttendee::updateOrCreate(['event_id' => $event->id, 'user_id' => $request->user()->id], $data);

        return response()->json(['message' => 'Teilnahme gespeichert.']);
    }

    private function visibleSites(Request $request)
    {
        $user = $request->user();

        return Site::query()
            ->where('is_active', true)
            ->when(! $user->can('view sites'), fn ($q) => $q->whereExists(function ($sub) use ($user) {
                $sub->selectRaw(1)->from('site_group')
                    ->join('group_user', 'site_group.group_id', '=', 'group_user.group_id')
                    ->whereColumn('site_group.site_id', 'sites.id')
                    ->where('group_user.user_id', $user->id);
            }))
            ->orderBy('name');
    }
}

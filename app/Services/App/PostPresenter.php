<?php

namespace App\Services\App;

use App\Model\Post;
use App\Model\User;
use App\Model\UserRueckmeldungen;
use DevDojo\LaravelReactions\Models\Reaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Baut die App-Darstellung von Beiträgen (B-10). Alle Zusatzdaten werden für die ganze
 * Seite gesammelt geladen – eine feste Zahl an Abfragen statt mehrerer pro Beitrag.
 */
class PostPresenter
{
    public function __construct(private readonly User $user) {}

    /** @param Collection<int,Post> $posts */
    public function list(Collection $posts): array
    {
        $ctx = $this->context($posts, false);

        return $posts->map(fn (Post $post) => $this->present($post, $ctx, false))->values()->all();
    }

    public function detail(Post $post): array
    {
        $post->loadMissing(['rueckmeldung.options', 'media', 'autor:id,name', 'poll']);
        $post->loadCount('comments');

        return $this->present($post, $this->context(collect([$post]), true), true);
    }

    private function context(Collection $posts, bool $detail): array
    {
        $ids = $posts->pluck('id');
        $family = Family::userIds($this->user);

        $reactionRows = DB::table('reactables')
            ->join('reactions', 'reactions.id', '=', 'reactables.reaction_id')
            ->where('reactables.reactable_type', Post::class)
            ->whereIn('reactables.reactable_id', $ids)
            ->select('reactables.reactable_id', 'reactions.name', 'reactables.responder_id', 'reactables.responder_type')
            ->get();

        $pollIds = $posts->pluck('poll.id')->filter();

        return [
            'available_reactions' => Reaction::query()->pluck('name')->all(),
            'reactions' => $reactionRows->groupBy('reactable_id'),
            'confirmed' => DB::table('read_receipts')->whereIn('post_id', $ids)->whereIn('user_id', $family)
                ->whereNotNull('confirmed_at')->pluck('post_id')->flip(),
            'responses' => UserRueckmeldungen::query()
                ->whereIn('post_id', $ids)
                ->whereIn('users_id', $family)
                ->when($detail, fn ($q) => $q->with(['answers.option', 'user:id,name']))
                ->orderBy('created_at')
                ->get()
                ->groupBy('post_id'),
            'voted' => $pollIds->isEmpty() ? collect() : DB::table('votes')->whereIn('poll_id', $pollIds)
                ->where('author_id', $this->user->id)->pluck('poll_id')->flip(),
        ];
    }

    private function present(Post $post, array $ctx, bool $detail): array
    {
        $images = $post->media->filter(fn (Media $m) => str_starts_with((string) $m->mime_type, 'image/'));
        $responses = $ctx['responses']->get($post->id, collect());
        $r = $post->rueckmeldung;
        $reactions = $ctx['reactions']->get($post->id, collect());
        $mine = $reactions->first(fn ($row) => (int) $row->responder_id === $this->user->id
            && $row->responder_type === get_class($this->user))?->name;
        $readRequired = (bool) $post->read_receipt;
        $readConfirmed = $ctx['confirmed']->has($post->id);
        $feedbackOpen = $r && FeedbackService::isOpen($r);

        $data = [
            'id' => $post->id,
            'header' => $post->header,
            'excerpt' => Str::limit(trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(str_replace(['<br', '</p>', '</li>'], [' <br', ' </p>', ' </li>'], (string) $post->news))))), 200),
            'author' => $post->autor?->name ?? config('app.name'),
            'sticky' => (bool) $post->sticky,
            'external' => (bool) $post->external,
            'created_at' => $post->created_at?->toIso8601String(),
            'updated_at' => $post->updated_at?->toIso8601String(),
            'archiv_ab' => $post->archiv_ab?->toIso8601String(),
            'attachments_count' => $post->media->count(),
            'thumb_url' => ($first = $images->first()) ? $this->mediaUrl($first, 'thumb') : null,
            'read_receipt' => [
                'required' => $readRequired,
                'confirmed' => $readConfirmed,
                'deadline' => $post->read_receipt_deadline?->toIso8601String(),
            ],
            'feedback' => $r ? [
                'type' => $r->type,
                'required' => (bool) $r->pflicht,
                'open' => $feedbackOpen,
                'deadline' => $r->ende?->toIso8601String(),
                'multiple' => (bool) $r->multiple,
                'responded' => $responses->isNotEmpty(),
                'liste_id' => $r->liste_id,
            ] : null,
            'poll' => $post->poll ? ['id' => $post->poll->id, 'voted' => $ctx['voted']->has($post->poll->id)] : null,
            'comments' => [
                'enabled' => $r && ($r->type === 'commentable' || $r->commentable),
                'count' => (int) ($post->comments_count ?? 0),
            ],
            'reactions' => [
                'enabled' => (bool) $post->reactable,
                'available' => $ctx['available_reactions'],
                'counts' => $reactions->countBy('name')->all(),
                'mine' => $mine,
            ],
            'todo' => $readRequired && ! $readConfirmed
                ? 'read_receipt'
                : ($r && $r->pflicht && $feedbackOpen && $responses->isEmpty() && in_array($r->type, ['email', 'text', 'abfrage', 'bild'], true) ? 'feedback' : null),
        ];

        if (! $detail) {
            return $data;
        }

        return $data + [
            // Bereinigtes HTML – die App rendert es selbst (keine WebView).
            'news_html' => HtmlSanitizer::clean((string) $post->news),
            'media' => $post->media->sortBy('order_column')->values()->map(fn (Media $m) => [
                'id' => $m->id,
                'uuid' => $m->uuid,
                'name' => $m->name ?: $m->file_name,
                'file_name' => $m->file_name,
                'mime_type' => $m->mime_type,
                'size' => $m->size,
                'collection' => $m->collection_name,
                'url' => route('api.files.download', ['media_uuid' => $m->uuid]),
                'thumb_url' => str_starts_with((string) $m->mime_type, 'image/') ? $this->mediaUrl($m, 'thumb') : null,
                'preview_url' => str_starts_with((string) $m->mime_type, 'image/') ? $this->mediaUrl($m, 'preview') : null,
                'own' => (int) $m->getCustomProperty('uploaded_by') === $this->user->id,
            ])->all(),
            'feedback_details' => $r ? [
                'text' => $r->text,
                'max_answers' => $r->max_answers,
                'options' => $r->type === 'abfrage' ? $r->options->map(fn ($o) => [
                    'id' => $o->id,
                    'type' => $o->type,
                    'option' => $o->option,
                    'required' => (bool) $o->required,
                ])->values()->all() : [],
                'responses' => $responses->map(fn (UserRueckmeldungen $u) => [
                    'id' => $u->id,
                    'text' => trim((string) $u->text) ?: null,
                    'author' => $u->user?->name,
                    'own' => (int) $u->users_id === $this->user->id,
                    'created_at' => $u->created_at?->toIso8601String(),
                    'updated_at' => $u->updated_at?->toIso8601String(),
                    'answers' => $u->answers->map(fn ($a) => [
                        'option_id' => $a->option_id,
                        'answer' => $a->answer,
                    ])->values()->all(),
                ])->values()->all(),
            ] : null,
        ];
    }

    private function mediaUrl(Media $media, string $size): string
    {
        return route('api.files.download', ['media_uuid' => $media->uuid]).'?size='.$size;
    }
}

<?php

namespace App\Services;

use App\Model\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class HelpService
{
    /**
     * Liefert alle Topics, die für den gegebenen User sichtbar sind.
     */
    public function availableTopics(?User $user = null): Collection
    {
        $user ??= auth()->user();

        return collect(config('help.topics', []))
            ->filter(fn (array $topic) => $this->userCanSee($user, $topic))
            ->map(fn (array $topic) => $this->normalize($topic))
            ->sortBy(fn ($t) => [$t['group'], $t['order'], $t['title']])
            ->values();
    }

    /**
     * Liefert die für den aktuellen Routen-Kontext passenden Topics.
     */
    public function topicsForRoute(?string $routeName, ?string $uri, ?User $user = null): Collection
    {
        $available = $this->availableTopics($user);

        if (! $routeName && ! $uri) {
            return collect();
        }

        $candidates = array_values(array_filter([$routeName, ltrim((string) $uri, '/')]));

        return $available->filter(function (array $topic) use ($candidates) {
            foreach ($topic['routes'] ?? [] as $pattern) {
                foreach ($candidates as $candidate) {
                    if ($candidate !== '' && Str::is($pattern, $candidate)) {
                        return true;
                    }
                }
            }

            return false;
        })->values();
    }

    /**
     * Findet ein Topic per Slug (oder gibt null zurück, wenn der User keine Permission hat).
     */
    public function find(string $slug, ?User $user = null): ?array
    {
        return $this->availableTopics($user)
            ->firstWhere('slug', $slug);
    }

    /**
     * Topics nach Gruppe gruppiert (für Übersichtsseite).
     */
    public function grouped(?User $user = null): Collection
    {
        $groups = config('help.groups', []);

        return $this->availableTopics($user)
            ->groupBy('group')
            ->sortBy(fn ($items, $key) => array_search($key, array_keys($groups), true) ?: 999);
    }

    /**
     * Rendert den Markdown-Inhalt eines Topics zu HTML.
     */
    public function renderContent(array $topic): string
    {
        $file = $this->resolveFilePath($topic);

        if (! $file || ! File::exists($file)) {
            return '<p class="text-gray-500 italic">Für dieses Thema ist noch keine Anleitung hinterlegt.</p>';
        }

        $ttl = (int) config('help.cache_ttl', 0);
        $key = 'help.content.'.md5($file).'.'.@filemtime($file);

        $render = fn () => $this->convertMarkdown(File::get($file));

        return $ttl > 0 ? Cache::remember($key, $ttl, $render) : $render();
    }

    public function groupLabel(string $key): string
    {
        return config("help.groups.$key", Str::headline($key));
    }

    /* ----------------------------------------------------------------- */

    protected function userCanSee(?User $user, array $topic): bool
    {
        if (! empty($topic['permission'])) {
            return $user && $user->can($topic['permission']);
        }

        if (! empty($topic['role'])) {
            return $user && method_exists($user, 'hasRole') && $user->hasRole($topic['role']);
        }

        if (! empty($topic['gate'])) {
            return $user && \Illuminate\Support\Facades\Gate::forUser($user)->allows($topic['gate']);
        }

        // Ohne Restriktion -> für jeden eingeloggten User sichtbar
        return $user !== null;
    }

    protected function normalize(array $topic): array
    {
        return array_merge([
            'slug'       => '',
            'title'      => '',
            'excerpt'    => '',
            'icon'       => 'fas fa-circle-question',
            'group'      => 'erste-schritte',
            'permission' => null,
            'role'       => null,
            'routes'     => [],
            'order'      => 100,
            'site_id'    => null,
            'file'       => null,
        ], $topic);
    }

    protected function resolveFilePath(array $topic): ?string
    {
        $base = resource_path(config('help.content_path', 'help'));
        $file = $topic['file'] ?? ($topic['slug'].'.md');

        return $base.DIRECTORY_SEPARATOR.$file;
    }

    protected function convertMarkdown(string $markdown): string
    {
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input'         => 'escape',
            'allow_unsafe_links' => false,
        ]);

        return (string) $converter->convert($markdown);
    }
}

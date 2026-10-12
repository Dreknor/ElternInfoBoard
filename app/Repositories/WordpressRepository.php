<?php

namespace App\Repositories;

use App\Exceptions\WordpressPushException;
use App\Model\Module;
use App\Model\Post;
use App\Services\Wordpress\GutenbergConverter;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Überträgt Nachrichten als Beiträge auf die WordPress-Homepage (REST-API).
 *
 * Ablauf:
 *  1. Neuer Beitrag wird zunächst als Entwurf angelegt (damit Bilder ihm zugeordnet werden
 *     können und nie ein halbfertiger Beitrag öffentlich sichtbar ist bzw. Benachrichtigungen auslöst).
 *  2. Beitragsbild und Bilder werden hochgeladen – bereits übertragene Medien werden
 *     wiederverwendet, damit die Mediathek bei jeder Änderung nicht erneut gefüllt wird.
 *  3. Der Inhalt wird als Gutenberg-Blöcke (Text, Galerie, Downloads) gespeichert und
 *     der Beitrag im finalen Status veröffentlicht.
 *
 * Fehler werfen eine WordpressPushException, damit der Job erneut versucht wird.
 */
class WordpressRepository
{
    private GutenbergConverter $converter;

    public function __construct(?GutenbergConverter $converter = null)
    {
        $this->converter = $converter ?? new GutenbergConverter;
    }

    /**
     * Basis-URL der WordPress-Seite (mit Schema, ohne abschließenden Slash).
     */
    public static function siteUrl(): ?string
    {
        $url = trim((string) config('wordpress.wp_url'));

        if ($url === '') {
            return null;
        }

        if (! preg_match('~^https?://~i', $url)) {
            $url = 'https://'.$url;
        }

        return rtrim($url, '/');
    }

    /**
     * Ist das Modul aktiv und darf der angemeldete Benutzer nach WordPress übertragen?
     */
    public static function pushAllowedFor($user): bool
    {
        if (! $user || ! $user->can('push to wordpress')) {
            return false;
        }

        return (bool) (Module::firstWhere('setting', 'Push to WordPress')?->options['active'] ?? false);
    }

    public function isConfigured(): bool
    {
        return self::siteUrl() !== null
            && filled(config('wordpress.wp_username'))
            && filled(config('wordpress.wp_password'));
    }

    /**
     * Pusht einen Post zu WordPress (anlegen oder aktualisieren).
     *
     * @throws WordpressPushException
     */
    public function pushPost(Post $post): void
    {
        if (! $this->isConfigured()) {
            throw new WordpressPushException('WordPress-Push ist nicht konfiguriert (WP_URL, WP_USER_NAME, WP_PASSWORD).');
        }

        $wpPostId = $post->published_wp_id ?: $this->createDraft($post);

        $images = $this->images($post);
        $header = $post->getMedia('header')->first(fn (Media $media) => $this->isImage($media));

        // Ohne eigenes Header-Bild wird das erste Bild zum Beitragsbild und nicht zusätzlich in der Galerie gezeigt
        if (! $header && $images->isNotEmpty()) {
            $header = $images->shift();
        }

        $featured = $header ? $this->ensureMedia($post, $header, $wpPostId) : null;

        $gallery = $images
            ->map(fn (Media $media) => $this->ensureMedia($post, $media, $wpPostId))
            ->filter()
            ->map(fn (array $wpMedia) => $this->imageData($wpMedia))
            ->values()
            ->all();

        $files = [];
        if (config('wordpress.push_files')) {
            foreach ($this->attachments($post) as $media) {
                $wpMedia = $this->ensureMedia($post, $media, $wpPostId);
                if ($wpMedia) {
                    $files[] = $this->converter->fileBlock($wpMedia['id'], $wpMedia['source_url'], $media->name ?: $media->file_name);
                }
            }
        }

        $content = $this->converter->joinBlocks([
            $this->converter->convert($post->news),
            $this->converter->galleryBlock($gallery),
            ...$files,
        ]);

        $data = [
            'title' => $post->header,
            'content' => $content,
            'excerpt' => $this->converter->excerpt($post->news),
            'status' => $post->released ? 'publish' : 'draft',
            // 0 entfernt ein zuvor gesetztes Beitragsbild
            'featured_media' => $featured['id'] ?? 0,
        ];

        if (! empty(config('wordpress.categories'))) {
            $data['categories'] = config('wordpress.categories');
        }

        $response = $this->client()->post('posts/'.$wpPostId, $data);

        // Beitrag wurde in WordPress endgültig gelöscht: neu anlegen
        if ($response->status() === 404 && $post->published_wp_id) {
            $response = $this->client()->post('posts', $data + ['slug' => $this->slug($post)]);

            if ($response->successful()) {
                $this->rememberWordpressId($post, (int) $response->json('id'));
            }
        }

        $this->ensureSuccessful($response, 'Beitrag speichern', $post);
    }

    /**
     * Legt einen leeren Entwurf an und merkt sich dessen ID.
     */
    private function createDraft(Post $post): int
    {
        $response = $this->client()->post('posts', [
            'title' => $post->header,
            'slug' => $this->slug($post),
            'status' => 'draft',
        ]);

        $this->ensureSuccessful($response, 'Entwurf anlegen', $post);

        $id = (int) $response->json('id');

        if ($id <= 0) {
            throw new WordpressPushException("WordPress hat beim Anlegen von Nachricht {$post->id} keine Beitrags-ID geliefert.");
        }

        $this->rememberWordpressId($post, $id);

        return $id;
    }

    private function rememberWordpressId(Post $post, int $id): void
    {
        // Ohne Events speichern, damit der Observer keinen weiteren Push auslöst
        $post->published_wp_id = $id;
        $post->saveQuietly(['timestamps' => false]);

        Post::bumpCacheVersion();
    }

    /**
     * Liefert das WordPress-Medium zu einer Datei. Bereits hochgeladene Dateien werden
     * wiederverwendet; fehlen sie in WordPress (gelöscht), werden sie neu hochgeladen.
     *
     * @return array<string, mixed>|null Medien-Objekt der REST-API
     */
    private function ensureMedia(Post $post, Media $media, int $wpPostId): ?array
    {
        $known = $media->getCustomProperty('wordpress');

        if (is_array($known) && ($known['site'] ?? null) === self::siteUrl() && ! empty($known['id'])) {
            $response = $this->client()->get('media/'.$known['id'], ['context' => 'edit']);

            if ($response->successful()) {
                return $response->json();
            }

            if (! in_array($response->status(), [404, 410], true)) {
                $this->ensureSuccessful($response, 'Medium abrufen', $post);
            }
        }

        return $this->uploadMedia($post, $media, $wpPostId);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function uploadMedia(Post $post, Media $media, int $wpPostId): ?array
    {
        [$disk, $path] = $this->sourceFile($media);

        if (! Storage::disk($disk)->exists($path)) {
            report(new WordpressPushException("Datei für Medium {$media->id} fehlt ({$path}) und wird nicht übertragen."));

            return null;
        }

        $mimeType = Storage::disk($disk)->mimeType($path) ?: $media->mime_type;
        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: pathinfo($media->file_name, PATHINFO_EXTENSION);
        $fileName = (Str::slug($media->name ?: pathinfo($media->file_name, PATHINFO_FILENAME), '-', 'de') ?: 'datei-'.$media->id)
            .'.'.strtolower($extension);

        $query = http_build_query(array_filter([
            'post' => $wpPostId,
            'title' => $this->isImage($media) ? $post->header : ($media->name ?: $media->file_name),
            'alt_text' => $this->isImage($media) ? $this->altText($post, $media) : null,
            'caption' => (string) $media->getCustomProperty('caption', ''),
        ], fn ($value) => $value !== null && $value !== ''));

        $response = $this->client()
            ->withHeaders(['Content-Disposition' => 'attachment; filename="'.$fileName.'"'])
            ->withBody(Storage::disk($disk)->get($path), $mimeType)
            ->post('media?'.$query);

        $this->ensureSuccessful($response, "Medium {$media->file_name} hochladen", $post);

        $wpMedia = $response->json();

        $media->setCustomProperty('wordpress', [
            'site' => self::siteUrl(),
            'id' => (int) $wpMedia['id'],
        ]);
        $media->save();

        return $wpMedia;
    }

    /**
     * Für Bilder wird die verkleinerte Fassung (1200 px, korrekt gedreht) hochgeladen,
     * sofern vorhanden – Handyfotos im Original sind oft mehrere MB groß.
     *
     * @return array{0: string, 1: string} Disk und Pfad
     */
    private function sourceFile(Media $media): array
    {
        if ($this->isImage($media) && $media->hasGeneratedConversion('preview')) {
            return [$media->conversions_disk ?: $media->disk, $media->getPathRelativeToRoot('preview')];
        }

        return [$media->disk, $media->getPathRelativeToRoot()];
    }

    /**
     * Bereitet ein WordPress-Medium für den Bild-Block auf.
     *
     * @param  array<string, mixed>  $wpMedia
     * @return array{id:int, url:string, full:string, size:string, alt:string, caption:string}
     */
    private function imageData(array $wpMedia): array
    {
        $large = data_get($wpMedia, 'media_details.sizes.large.source_url');

        return [
            'id' => (int) $wpMedia['id'],
            'url' => $large ?: $wpMedia['source_url'],
            'full' => $wpMedia['source_url'],
            'size' => $large ? 'large' : 'full',
            'alt' => (string) ($wpMedia['alt_text'] ?? ''),
            'caption' => trim(strip_tags((string) (data_get($wpMedia, 'caption.raw') ?? data_get($wpMedia, 'caption.rendered') ?? ''))),
        ];
    }

    private function altText(Post $post, Media $media): string
    {
        $caption = trim((string) $media->getCustomProperty('caption', ''));

        return $caption !== '' ? $caption : 'Bild zu: '.$post->header;
    }

    /**
     * Alle Bilder des Posts (Bilder-Sammlung und Bilder unter den Dateien) in Reihenfolge.
     *
     * @return Collection<int, Media>
     */
    private function images(Post $post): Collection
    {
        return $post->getMedia('images')
            ->merge($post->getMedia('files'))
            ->filter(fn (Media $media) => $this->isImage($media))
            ->values();
    }

    /**
     * @return Collection<int, Media>
     */
    private function attachments(Post $post): Collection
    {
        return $post->getMedia('files')
            ->reject(fn (Media $media) => $this->isImage($media))
            ->values();
    }

    private function isImage(Media $media): bool
    {
        return Str::startsWith((string) $media->mime_type, 'image/');
    }

    private function slug(Post $post): string
    {
        return Str::slug($post->header, '-', 'de');
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(self::siteUrl().'/wp-json/wp/v2/')
            ->withBasicAuth((string) config('wordpress.wp_username'), (string) config('wordpress.wp_password'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout(15)
            ->timeout((int) config('wordpress.timeout', 60));
    }

    /**
     * @throws WordpressPushException
     */
    private function ensureSuccessful(Response $response, string $action, Post $post): void
    {
        if ($response->successful() && is_array($response->json())) {
            return;
        }

        $message = $response->json('message') ?: Str::limit(strip_tags($response->body()), 300);

        throw new WordpressPushException(sprintf(
            'WordPress-Push von Nachricht %d fehlgeschlagen (%s): HTTP %d – %s',
            $post->id,
            $action,
            $response->status(),
            $message
        ));
    }
}

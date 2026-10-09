<?php

namespace Tests\Feature;

use App\Exceptions\WordpressPushException;
use App\Jobs\PushPostToWordpress;
use App\Model\Module;
use App\Model\Post;
use App\Model\User;
use App\Repositories\WordpressRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WordpressPushTest extends TestCase
{
    use RefreshDatabase;

    private int $nextMediaId = 100;

    /** @var array<int, int> IDs der in WordPress gelöschten Medien */
    private array $deletedMedia = [];

    private bool $unauthorized = false;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'wordpress.wp_url' => 'wp.test',
            'wordpress.wp_username' => 'elterninfo',
            'wordpress.wp_password' => 'app-password',
            'wordpress.categories' => [7],
            'media-library.queue_conversions_by_default' => false,
        ]);

        Storage::fake('public');
        $this->fakeWordpress();
    }

    private function fakeWordpress(): void
    {
        Http::fake(function (Request $request) {
            $path = parse_url($request->url(), PHP_URL_PATH);

            if ($this->unauthorized) {
                return Http::response(['code' => 'rest_cannot_create', 'message' => 'Keine Berechtigung'], 401);
            }

            if ($request->method() === 'POST' && $path === '/wp-json/wp/v2/posts') {
                return Http::response(['id' => 42, 'status' => $request['status']], 201);
            }

            if ($request->method() === 'POST' && $path === '/wp-json/wp/v2/posts/42') {
                return Http::response(['id' => 42, 'status' => $request['status']], 200);
            }

            if ($request->method() === 'POST' && $path === '/wp-json/wp/v2/media') {
                return Http::response($this->wpMedia($this->nextMediaId++), 201);
            }

            if ($request->method() === 'GET' && preg_match('~^/wp-json/wp/v2/media/(\d+)$~', $path, $match)) {
                return in_array((int) $match[1], $this->deletedMedia, true)
                    ? Http::response(['code' => 'rest_post_invalid_id', 'message' => 'Ungültige ID.'], 404)
                    : Http::response($this->wpMedia((int) $match[1]), 200);
            }

            return Http::response(['message' => 'unerwartet'], 500);
        });
    }

    private function wpMedia(int $id): array
    {
        return [
            'id' => $id,
            'source_url' => "https://wp.test/uploads/{$id}.jpg",
            'alt_text' => 'Bild zu: Sommerfest',
            'caption' => ['raw' => ''],
            'media_details' => ['sizes' => ['large' => ['source_url' => "https://wp.test/uploads/{$id}-1024x768.jpg"]]],
        ];
    }

    private function postWithImages(int $images = 2): Post
    {
        $post = Post::factory()->create([
            'header' => 'Sommerfest',
            'news' => '<p>Liebe Eltern,</p><p>&nbsp;</p><p>es war <strong>toll</strong>.</p>',
            'released' => 1,
        ]);

        $post->addMedia(UploadedFile::fake()->image('header.jpg', 1600, 900))->toMediaCollection('header');

        for ($i = 1; $i <= $images; $i++) {
            $post->addMedia(UploadedFile::fake()->image("foto{$i}.jpg", 800, 600))->toMediaCollection('images');
        }

        return $post->fresh();
    }

    /**
     * @return array<int, Request>
     */
    private function requests(string $method, string $path): array
    {
        return Http::recorded(fn (Request $request) => $request->method() === $method
            && parse_url($request->url(), PHP_URL_PATH) === $path)
            ->map(fn ($pair) => $pair[0])
            ->values()
            ->all();
    }

    /** @test */
    public function new_post_is_created_as_draft_and_published_with_blocks_and_gallery(): void
    {
        $post = $this->postWithImages();

        (new WordpressRepository)->pushPost($post);

        $this->assertSame(42, (int) $post->fresh()->published_wp_id);

        // Erst ein Entwurf, damit nie ein unfertiger Beitrag öffentlich ist
        $create = $this->requests('POST', '/wp-json/wp/v2/posts');
        $this->assertCount(1, $create);
        $this->assertSame('draft', $create[0]['status']);
        $this->assertSame('sommerfest', $create[0]['slug']);

        // Header + 2 Bilder
        $this->assertCount(3, $this->requests('POST', '/wp-json/wp/v2/media'));

        $update = $this->requests('POST', '/wp-json/wp/v2/posts/42');
        $this->assertCount(1, $update);
        $data = $update[0]->data();

        $this->assertSame('publish', $data['status']);
        $this->assertSame(100, $data['featured_media']);
        $this->assertSame([7], $data['categories']);
        $this->assertSame('Liebe Eltern, es war toll.', $data['excerpt']);
        $this->assertStringContainsString("<!-- wp:paragraph -->\n<p>es war <strong>toll</strong>.</p>", $data['content']);
        $this->assertStringContainsString('<!-- wp:gallery {"linkTo":"media"} -->', $data['content']);
        $this->assertStringContainsString('class="wp-image-101"', $data['content']);
        $this->assertStringContainsString('class="wp-image-102"', $data['content']);
        $this->assertStringNotContainsString('&nbsp;', $data['content']);
    }

    /** @test */
    public function images_are_not_uploaded_again_on_update(): void
    {
        $post = $this->postWithImages();
        $repository = new WordpressRepository;

        $repository->pushPost($post);
        $post->update(['news' => '<p>Neuer Text</p>']);
        $repository->pushPost($post->fresh());

        $this->assertCount(3, $this->requests('POST', '/wp-json/wp/v2/media'));
        $this->assertCount(1, $this->requests('POST', '/wp-json/wp/v2/posts'));

        $lastUpdate = collect($this->requests('POST', '/wp-json/wp/v2/posts/42'))->last()->data();
        $this->assertStringContainsString('Neuer Text', $lastUpdate['content']);
        $this->assertStringContainsString('class="wp-image-101"', $lastUpdate['content']);
    }

    /** @test */
    public function images_deleted_in_wordpress_are_uploaded_again(): void
    {
        $post = $this->postWithImages(1);
        $repository = new WordpressRepository;

        $repository->pushPost($post);
        $this->deletedMedia = [101];
        $repository->pushPost($post->fresh());

        $this->assertCount(3, $this->requests('POST', '/wp-json/wp/v2/media'));
        $this->assertSame(102, $post->getMedia('images')->first()->fresh()->getCustomProperty('wordpress')['id']);
    }

    /** @test */
    public function first_image_becomes_featured_image_when_no_header_exists(): void
    {
        $post = Post::factory()->create(['header' => 'Ausflug', 'news' => 'Text', 'released' => 0]);
        $post->addMedia(UploadedFile::fake()->image('a.jpg'))->toMediaCollection('images');
        $post->addMedia(UploadedFile::fake()->image('b.jpg'))->toMediaCollection('images');

        (new WordpressRepository)->pushPost($post->fresh());

        $data = $this->requests('POST', '/wp-json/wp/v2/posts/42')[0]->data();

        $this->assertSame('draft', $data['status']);
        $this->assertSame(100, $data['featured_media']);
        // Das zweite Bild steht als Einzelbild im Text, nicht als Galerie
        $this->assertStringContainsString('<!-- wp:image {"id":101', $data['content']);
        $this->assertStringNotContainsString('wp-image-100', $data['content']);
        $this->assertStringNotContainsString('wp:gallery', $data['content']);
    }

    /** @test */
    public function failed_requests_throw_so_the_job_is_retried(): void
    {
        $this->unauthorized = true;

        $this->expectException(WordpressPushException::class);
        $this->expectExceptionMessage('Keine Berechtigung');

        (new WordpressRepository)->pushPost(Post::factory()->create());
    }

    /** @test */
    public function observer_only_pushes_published_posts_on_relevant_changes(): void
    {
        Queue::fake();

        Module::factory()->create([
            'setting' => 'Push to WordPress',
            'category' => 'setting',
            'description' => 'Wordpress-Integration',
            'options' => ['active' => '1'],
        ]);
        Permission::findOrCreate('push to wordpress', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('push to wordpress');
        $this->actingAs($user);

        $post = Post::factory()->create(['published_wp_id' => 42, 'released' => 1]);

        $post->update(['send_at' => now()]);
        Queue::assertNotPushed(PushPostToWordpress::class);

        $post->update(['news' => '<p>Korrektur</p>']);
        Queue::assertPushed(PushPostToWordpress::class, 1);
    }
}

<?php

namespace Tests\Feature\API;

use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class FilesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected ?User $user = null;

    public function setUp(): void
    {
        parent::setUp();

        /** @var User $user */
        $user = User::factory()->create();
        $this->user = $user;
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_requires_authentication_to_access_files()
    {
        $this->withoutMiddleware(['auth:sanctum']);

        $response = $this->getJson('/api/files');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_list_all_accessible_files()
    {
        $response = $this->getJson('/api/files');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'from',
                    'to',
                ],
            ]);
    }

    /** @test */
    public function it_can_paginate_files()
    {
        $response = $this->getJson('/api/files?page=1&per_page=10');

        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 10,
                ],
            ]);
    }

    /** @test */
    public function it_limits_per_page_to_maximum_100()
    {
        $response = $this->getJson('/api/files?per_page=200');

        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'per_page' => 100,
                ],
            ]);
    }

    /** @test */
    public function it_can_search_files_by_name()
    {
        $response = $this->getJson('/api/files?search=document');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_sort_files_by_name()
    {
        $response = $this->getJson('/api/files?sort=name&order=asc');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_sort_files_by_size()
    {
        $response = $this->getJson('/api/files?sort=size&order=desc');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_sort_files_by_created_at()
    {
        $response = $this->getJson('/api/files?sort=created_at&order=desc');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_filter_files_by_mime_type()
    {
        $response = $this->getJson('/api/files?mime_type=application/pdf');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_validates_sort_parameter()
    {
        $response = $this->getJson('/api/files?sort=invalid_field');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort']);
    }

    /** @test */
    public function it_validates_order_parameter()
    {
        $response = $this->getJson('/api/files?order=invalid_order');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order']);
    }

    /** @test */
    public function it_returns_file_metadata_in_correct_format()
    {
        $response = $this->getJson('/api/files');

        if ($response->json('meta.total') > 0) {
            $response->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'name',
                        'file_name',
                        'mime_type',
                        'size',
                        'human_readable_size',
                        'url',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
        }
    }

    /** @test */
    public function it_can_get_mime_types_list()
    {
        $response = $this->getJson('/api/files/mime-types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [],
            ]);
    }

    /** @test */
    public function it_returns_mime_type_with_count_and_label()
    {
        $response = $this->getJson('/api/files/mime-types');

        if (count($response->json('data')) > 0) {
            $response->assertJsonStructure([
                'data' => [
                    '*' => [
                        'mime_type',
                        'count',
                        'label',
                    ],
                ],
            ]);
        }
    }

    /** @test */
    public function it_can_get_file_statistics()
    {
        $response = $this->getJson('/api/files/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_files',
                    'total_size',
                    'total_size_formatted',
                    'by_mime_type',
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_file()
    {
        $response = $this->getJson('/api/files/non-existent-uuid');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'File not found or access denied.',
                'error' => 'NOT_FOUND',
            ]);
    }

    /** @test */
    public function it_formats_file_sizes_correctly()
    {
        $controller = new \App\Http\Controllers\API\FilesController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('formatBytes');

        $this->assertEquals('0 B', $method->invoke($controller, 0));
        $this->assertEquals('1 KB', $method->invoke($controller, 1024));
        $this->assertEquals('1 MB', $method->invoke($controller, 1048576));
        $this->assertEquals('1 GB', $method->invoke($controller, 1073741824));
    }

    /** @test */
    public function it_provides_correct_mime_type_labels()
    {
        $controller = new \App\Http\Controllers\API\FilesController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getMimeTypeLabel');

        $this->assertEquals('PDF Document', $method->invoke($controller, 'application/pdf'));
        $this->assertEquals('JPEG Image', $method->invoke($controller, 'image/jpeg'));
        $this->assertEquals('PNG Image', $method->invoke($controller, 'image/png'));
        $this->assertEquals('Word Document (DOCX)',
            $method->invoke($controller, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'));
    }

    /** @test */
    public function it_returns_unknown_mime_type_as_is()
    {
        $controller = new \App\Http\Controllers\API\FilesController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getMimeTypeLabel');

        $this->assertEquals('application/unknown', $method->invoke($controller, 'application/unknown'));
    }
}


<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class FileCollection
 *
 * API Resource Collection for file data with pagination support.
 *
 * @package App\Http\Resources
 */
class FileCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = FileResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->total() ?? $this->collection->count(),
                'current_page' => $this->currentPage() ?? 1,
                'per_page' => $this->perPage() ?? $this->collection->count(),
                'last_page' => $this->lastPage() ?? 1,
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ],
        ];
    }

    /**
     * Add additional meta data to the collection.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'links' => [
                'self' => $request->url(),
            ],
        ];
    }
}


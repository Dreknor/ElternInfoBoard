<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class FileResource
 *
 * API Resource for file data transformation.
 *
 * @package App\Http\Resources
 */
class FileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_readable_size' => $this->formatBytes($this->size),
            'extension' => $this->extension ?? pathinfo($this->file_name, PATHINFO_EXTENSION),
            'collection_name' => $this->collection_name,
            'url' => route('api.files.download', ['media_uuid' => $this->uuid]),
            'preview_url' => $this->hasGeneratedConversion('thumb')
                ? $this->getUrl('thumb')
                : null,
            'custom_properties' => $this->custom_properties ?? [],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Format bytes to human readable size.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}


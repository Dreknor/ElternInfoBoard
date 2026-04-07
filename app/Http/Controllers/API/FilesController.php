<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Class FilesController
 * Controller for handling file related API requests.
 */
class FilesController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth:sanctum',
        ];
    }

    /**
     * Get all files accessible to the authenticated user.
     *
     * Returns a paginated list of files with metadata including file name,
     * size, MIME type, and download URL.
     *
     * @group Files
     *
     * @queryParam page integer Page number for pagination. Default: 1. Example: 1
     * @queryParam per_page integer Number of items per page (max 100). Default: 15. Example: 20
     * @queryParam search string Filter files by name. Example: document
     * @queryParam sort string Sort by field (name, size, created_at). Default: created_at. Example: name
     * @queryParam order string Sort order (asc, desc). Default: desc. Example: asc
     * @queryParam mime_type string Filter by MIME type. Example: application/pdf
     *
     * @responseField data array Array of file objects.
     * @responseField data[].id integer The file ID.
     * @responseField data[].uuid string The file UUID.
     * @responseField data[].name string The file name.
     * @responseField data[].file_name string The original file name.
     * @responseField data[].mime_type string The MIME type.
     * @responseField data[].size integer The file size in bytes.
     * @responseField data[].human_readable_size string The file size in human readable format.
     * @responseField data[].url string The download URL.
     * @responseField data[].created_at string The creation timestamp.
     * @responseField meta object Pagination metadata.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {


        $user = auth()->user();
        $files = $user->files();

        // Format response
        $formattedFiles = $files->map(function ($file) {
            return [
                'id' => $file->id,
                'uuid' => $file->uuid,
                'name' => $file->name,
                'file_name' => $file->file_name,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
                'human_readable_size' => $this->formatBytes($file->size),
                'created_at' => $file->created_at?->toISOString(),
                'updated_at' => $file->updated_at?->toISOString(),
            ];
        });

        return response()->json([
            'data' => $formattedFiles,
        ], 200);
    }

    /**
     * Get a single file by UUID.
     *
     * Returns detailed information about a specific file if the user has access to it.
     *
     * @group Files
     *
     * @urlParam uuid string required The UUID of the file. Example: 9a0e5c7f-1234-5678-9abc-def012345678
     *
     * @responseField id integer The file ID.
     * @responseField uuid string The file UUID.
     * @responseField name string The file name.
     * @responseField file_name string The original file name.
     * @responseField mime_type string The MIME type.
     * @responseField size integer The file size in bytes.
     * @responseField human_readable_size string The file size in human readable format.
     * @responseField url string The download URL.
     * @responseField created_at string The creation timestamp.
     * @responseField updated_at string The update timestamp.
     *
     * @return JsonResponse
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        $files = $user->files();

        $file = $files->firstWhere('uuid', $uuid);

        if (!$file) {
            return response()->json([
                'message' => 'File not found or access denied.',
                'error' => 'NOT_FOUND',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $file->id,
                'uuid' => $file->uuid,
                'name' => $file->name,
                'file_name' => $file->file_name,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
                'human_readable_size' => $this->formatBytes($file->size),
                'url' => route('api.files.download', ['media_uuid' => $file->uuid]),
                'created_at' => $file->created_at?->toISOString(),
                'updated_at' => $file->updated_at?->toISOString(),
                'custom_properties' => $file->custom_properties ?? [],
            ],
        ], 200);
    }

    /**
     * Get unique MIME types of all accessible files.
     *
     * Returns a list of all unique MIME types for filtering purposes.
     *
     * @group Files
     *
     * @responseField mime_types array Array of unique MIME types with their counts.
     *
     * @return JsonResponse
     */
    public function mimeTypes(Request $request): JsonResponse
    {
        $user = $request->user();
        $files = $user->files();

        $mimeTypes = $files->groupBy('mime_type')->map(function ($group, $mimeType) {
            return [
                'mime_type' => $mimeType,
                'count' => $group->count(),
                'label' => $this->getMimeTypeLabel($mimeType),
            ];
        })->values();

        return response()->json([
            'data' => $mimeTypes,
        ], 200);
    }

    /**
     * Get file statistics.
     *
     * Returns statistics about the user's accessible files.
     *
     * @group Files
     *
     * @responseField total_files integer Total number of files.
     * @responseField total_size integer Total size in bytes.
     * @responseField total_size_formatted string Total size in human readable format.
     * @responseField by_mime_type array File count grouped by MIME type.
     *
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $files = $user->files();

        $totalSize = $files->sum('size');
        $byMimeType = $files->groupBy('mime_type')->map->count();

        return response()->json([
            'data' => [
                'total_files' => $files->count(),
                'total_size' => $totalSize,
                'total_size_formatted' => $this->formatBytes($totalSize),
                'by_mime_type' => $byMimeType,
            ],
        ], 200);
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

    /**
     * Get a human-readable label for a MIME type.
     *
     * @param string $mimeType
     * @return string
     */
    private function getMimeTypeLabel(string $mimeType): string
    {
        $labels = [
            'application/pdf' => 'PDF Document',
            'image/jpeg' => 'JPEG Image',
            'image/png' => 'PNG Image',
            'image/gif' => 'GIF Image',
            'image/svg+xml' => 'SVG Image',
            'application/msword' => 'Word Document',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word Document (DOCX)',
            'application/vnd.ms-excel' => 'Excel Spreadsheet',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel Spreadsheet (XLSX)',
            'application/vnd.ms-powerpoint' => 'PowerPoint Presentation',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PowerPoint Presentation (PPTX)',
            'text/plain' => 'Text File',
            'text/csv' => 'CSV File',
            'application/zip' => 'ZIP Archive',
            'application/x-rar-compressed' => 'RAR Archive',
        ];

        return $labels[$mimeType] ?? $mimeType;
    }
}

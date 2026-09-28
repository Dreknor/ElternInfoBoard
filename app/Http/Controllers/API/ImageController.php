<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\App\MediaAccess;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    /**
     * Get file by uuid
     *
     * Get file by uuid
     *
     * @group Files
     *
     * @urlParam uuid required The uuid of the file
     *
     * @responseField file The file
     *
     * @return BinaryFileResponse
     */
    public function getFileByUuid(Request $request, $uuid)
    {
        $media = Media::where('uuid', $uuid)->firstOrFail();

        return $this->serve($request, $media);
    }

    /**
     * Get file by id
     *
     * Get file by id
     *
     * @group Files
     *
     * @urlParam media_id required The id of the file
     *
     * @queryParam size Vorschaugröße für Bilder: thumb (400 px) oder preview (1200 px). Example: thumb
     *
     * @responseField file The file
     *
     * @return BinaryFileResponse
     */
    public function getImage(Request $request, Media $media_id)
    {
        return $this->serve($request, $media_id);
    }

    /**
     * Zugriff prüfen (B-07) und – falls angefragt und vorhanden – eine verkleinerte Fassung liefern (B-12).
     */
    private function serve(Request $request, Media $media): BinaryFileResponse
    {
        abort_unless(MediaAccess::canView($request->user(), $media), 403, 'Zugriff verweigert.');

        $size = $request->query('size');
        $path = in_array($size, ['thumb', 'preview'], true) && $media->hasGeneratedConversion($size)
            ? $media->getPath($size)
            : $media->getPath();

        return response()->file($path, [
            'Content-Type' => $path === $media->getPath() ? $media->mime_type : (mime_content_type($path) ?: $media->mime_type),
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}

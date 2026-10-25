<?php

namespace App\Http\Controllers;

use App\Services\App\MediaAccess;
use App\Support\UploadGuard;
use Illuminate\Routing\Controllers\HasMiddleware;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }

    /**
     * @return Media|BinaryFileResponse
     */
    public function getImage(Media $media_id)
    {
        // Zugriff nur für Berechtigte (vorher: jede Datei per fortlaufender ID abrufbar).
        abort_unless(MediaAccess::canView(auth()->user(), $media_id), 403, 'Zugriff verweigert.');

        if (! str_starts_with((string) $media_id->mime_type, 'image/') || str_contains((string) $media_id->mime_type, 'svg')) {
            // Nicht-Bilder (und SVG, das Skripte enthalten kann) als Download ausliefern.
            return UploadGuard::harden($media_id->toResponse(request()), $media_id, false);
        }

        // Optional verkleinerte Fassung (z. B. für Galerie-Kacheln); Fallback aufs Original.
        $conversion = request()->query('conversion');
        if (in_array($conversion, ['thumb', 'preview'], true) && $media_id->hasGeneratedConversion($conversion)) {
            $path = $media_id->getPath($conversion);
            $mimeType = mime_content_type($path) ?: $media_id->mime_type;
        } else {
            $path = $media_id->getPath();
            $mimeType = $media_id->mime_type;
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $mimeType);

        return UploadGuard::harden($response, $media_id);
    }

    public function changeCollection(Media $media, string $collection_name)
    {
        $media->update([
            'collection_name' => $collection_name,
        ]);

        return redirect()->back()->with([
            'type' => 'success',
            'message' => 'Datei in '.$collection_name.' verschoben',
        ]);
    }
}

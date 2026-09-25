<?php

namespace App\Http\Controllers;

use App\Services\App\MediaAccess;
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

        if (! str_starts_with((string) $media_id->mime_type, 'image/')) {
            // Nicht-Bilder als Download ausliefern (Media ist Responsable).
            return $media_id;
        }

        $response = new BinaryFileResponse($media_id->getPath());
        $response->headers->set('Content-Disposition', 'inline; filename="'.$media_id->file_name.'"');
        $response->headers->set('Content-Type', $media_id->mime_type);

        return $response;
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

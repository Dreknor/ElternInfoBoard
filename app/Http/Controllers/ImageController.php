<?php

namespace App\Http\Controllers;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 *
 */
class ImageController extends Controller
{
    /**
     * Medien werden nur angezeigt, wenn der Benutzer angemeldet ist
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Media $media_id
     * @return Media|BinaryFileResponse
     */
    public function getImage(Media $media_id)
    {


        if ($media_id->collection_name != "images" and $media_id->collection_name != "header") {
            return $media_id;
        }

       $response = new BinaryFileResponse($media_id->getPath());
         $response->headers->set('Content-Disposition', 'inline; filename="' . $media_id->file_name . '"');
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
            'message' => 'Datei in ' . $collection_name . ' verschoben',
        ]);
    }
}

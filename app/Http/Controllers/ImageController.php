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

        return $response;
    }
}

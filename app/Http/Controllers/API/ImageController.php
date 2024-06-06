<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 *
 */
class ImageController extends Controller
{

    public function __construct()
    {
       // $this->middleware('auth:sanctum');
    }

    public function getFileByUuid(Request $request, $uuid)
    {
        Log::info($uuid);

        $media = Media::where('uuid', $uuid)->firstOrFail();

        return response()->file($media->getPath(), [
            'Content-Type' => $media->mime_type,
            'content-transfer-encoding' => 'binary',
        ]);

    }

    /**
     * @param Media $media_id
     * @return Media|BinaryFileResponse
     */
    public function getImage(Media $media_id)
    {


        return response()->file($media_id->getPath(), [
            'Content-Type' => $media_id->mime_type,
            'content-transfer-encoding' => 'binary',
        ]);
        return response()->file($media_id->getPath());

        if ($media_id->collection_name != "images" and $media_id->collection_name != "header") {
            return $media_id;
        }
        $response = new BinaryFileResponse($media_id->getPath());
        $response->headers->set('Content-Disposition', 'inline; filename="' . $media_id->file_name . '"');
        $response->headers->set('Content-Type', $media_id->mime_type);

        return $response;
    }
}

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
     * @param Request $request
     * @param $uuid
     * @return BinaryFileResponse
     */
    public function getFileByUuid(Request $request, $uuid)
    {
        $media = Media::where('uuid', $uuid)->firstOrFail();

        return response()->file($media->getPath(), [
            'Content-Type' => $media->mime_type,
            'content-transfer-encoding' => 'binary',
        ]);

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
     * @responseField file The file
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

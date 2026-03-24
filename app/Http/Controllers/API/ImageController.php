<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
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

        // Zugriffsschutz: Prüfen ob der authentifizierte User Zugriff auf diese Datei hat.
        // Dateien gehören zu Gruppen; der User muss Mitglied der Gruppe sein
        // oder die Berechtigung 'upload files' (Admin) haben.
        $user = $request->user();

        if (! $user->can('upload files')) {
            // Prüfen ob die Datei zu einer Gruppe gehört, in der der User Mitglied ist
            $model = $media->model;
            $userGroupIds = $user->groups()->pluck('groups.id');

            $hasAccess = false;

            if ($model instanceof \App\Model\Group) {
                $hasAccess = $userGroupIds->contains($model->id);
            } elseif ($model && method_exists($model, 'groups')) {
                // Post, Rueckmeldung etc. – über zugehörige Gruppen prüfen
                $modelGroupIds = $model->groups()->pluck('groups.id');
                $hasAccess = $userGroupIds->intersect($modelGroupIds)->isNotEmpty();
            } else {
                // Fallback: Für unbekannte Model-Typen Zugriff verweigern
                $hasAccess = false;
            }

            if (! $hasAccess) {
                abort(403, 'Zugriff verweigert.');
            }
        }

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
     *
     * @return Media|BinaryFileResponse
     */
    public function getImage(Media $media_id)
    {
        return response()->file($media_id->getPath(), [
            'Content-Type' => $media_id->mime_type,
            'content-transfer-encoding' => 'binary',
        ]);
    }
}

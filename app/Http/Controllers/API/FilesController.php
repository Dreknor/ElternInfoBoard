<?php

namespace App\Http\Controllers\API;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * Get all files.
     *
     * Get all files from the database.
     *
     * @group Files
     *
     * @responseField files array The files.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {

        $user = $request->user();

        $files = $user->files();

        return response()->json(
            ['files' => $files], 200
        );
    }
}

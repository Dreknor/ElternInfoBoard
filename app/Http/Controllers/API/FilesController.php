<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;


use App\Model\User;
use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

/**
 * Class FilesController
 * Controller for handling file related API requests.
 */
class FilesController extends Controller
{
    /**
     * Files constructor.
     *
     *
     * Apply authentication middleware.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }


    /**
     * Get all files.
     *
     * Get all files from the database.
     *
     * @group Files
     * @responseField files array The files.
     *
     *
     * @param Request $request
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

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;


use App\Model\User;
use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

class FilesController extends Controller
{

    /**
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {

        $user = $request->user();

        $files = $user->files();




        return response()->json(
            $files, 200
        );
    }


}

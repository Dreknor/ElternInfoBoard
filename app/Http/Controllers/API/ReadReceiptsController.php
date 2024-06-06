<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Post;
use App\Model\ReadReceipts;
use App\Model\User;
use Illuminate\Http\Request;

class ReadReceiptsController extends Controller
{

    public function store(Request $request, $post)
    {

        $user = User::first();
        //ToDo: Benutzerauthentifizierung

        try {
            ReadReceipts::firstOrCreate([
                'post_id' => $post,
                'user_id' => $user->id,
            ]);
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }
}

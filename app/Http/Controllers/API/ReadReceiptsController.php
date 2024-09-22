<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Post;
use App\Model\ReadReceipts;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Class ReadReceiptsController
 *
 * Controller for handling read receipts.
 */
class ReadReceiptsController extends Controller
{


    /**
     * Post: mark a post as read
     *
     * Store a new read receipt for a post. If the read receipt already exists, no new one is created.
     *
     * @group Nachrichten
     *
     * @param \Illuminate\Http\Request $request The incoming request instance.
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure.
     */
    public function store(Request $request, Post $post)
    {

        $user = $request->user();

        try {
            // Create a new read receipt if it doesn't already exist
            ReadReceipts::firstOrCreate([
                'post_id' => $post,
                'user_id' => $user->id,
            ]);
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            // Return an error response if an exception occurs
            Log::error($e->getMessage());
            return response()->json(['error' => 'Die Lesebestätigung konnte nicht verarbeitet werden.'], 500);
        }

    }
}

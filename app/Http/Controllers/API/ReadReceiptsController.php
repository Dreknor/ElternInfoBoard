<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Post;
use App\Model\ReadReceipts;
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
     * @urlParam post required The ID of the post. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Lesebestätigung gespeichert"
     * }
     *
     * @response 500 {
     *   "success": false,
     *   "error": "Die Lesebestätigung konnte nicht verarbeitet werden.",
     *   "message": "Ein Fehler ist aufgetreten"
     * }
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request instance.
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure.
     */
    public function store(Request $request, Post $post)
    {

        $user = $request->user();

        try {
            // Create a new read receipt if it doesn't already exist
            $receipt = ReadReceipts::firstOrCreate(
                [
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                ],
                [
                    'confirmed_at' => now(),
                ]
            );

            // Falls der Eintrag bereits existierte, aber noch nicht bestätigt war
            if ($receipt->wasRecentlyCreated === false && is_null($receipt->confirmed_at)) {
                $receipt->confirmed_at = now();
                $receipt->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Lesebestätigung gespeichert'
            ], 200);
        } catch (\Exception $e) {
            // Return an error response if an exception occurs
            Log::error('Fehler beim Speichern der Lesebestätigung: ', [
                'error' => $e->getMessage(),
                'post_id' => $post->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Die Lesebestätigung konnte nicht verarbeitet werden.',
                'message' => 'Ein Fehler ist aufgetreten'
            ], 500);
        }

    }
}

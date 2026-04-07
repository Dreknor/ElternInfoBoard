<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Comment;
use App\Model\Post;
use Illuminate\Http\Request;

/**
 * Class CommentController
 *
 * Controller for handling comment related API requests.
 */
class CommentController extends Controller
{
    /**
     * Get all comments for a post
     *
     * This method returns all comments for a specific post.
     *
     * @group Comments
     *
     * @urlParam post required The ID of the post. Example: 1
     *
     * @responseField comments array List of comments with author information.
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, Post $post)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        // Check if user has access to this post
        if (!$post->users->contains($user) && !$user->can('view all')) {
            return response()->json(['error' => 'User not allowed to view comments for this post'], 403);
        }

        $comments = $post->comments()
            ->with(['creator' => function ($query) {
                $query->select('id', 'name');
            }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'author' => $comment->creator->name ?? 'Unbekannt',
                    'author_id' => $comment->creator_id,
                    'created_at' => $comment->created_at->toIso8601String(),
                    'updated_at' => $comment->updated_at->toIso8601String(),
                ];
            });

        return response()->json([
            'comments' => $comments,
        ], 200);
    }

    /**
     * Store a new comment for a post
     *
     * This method creates a new comment for a specific post.
     *
     * @group Comments
     *
     * @urlParam post required The ID of the post. Example: 1
     *
     * @bodyParam body string required The comment text. Example: Das ist ein toller Beitrag!
     *
     * @responseField success string The success message.
     * @responseField comment object The created comment object.
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Post $post)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        // Check if user has access to this post
        if (!$post->users->contains($user) && !$user->can('view all')) {
            return response()->json(['error' => 'User not allowed to comment on this post'], 403);
        }

        // Validate request
        $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        // Create comment
        $comment = $post->comment(
            ['body' => $request->body],
            $user
        );

        return response()->json([
            'success' => 'Comment created successfully',
            'comment' => [
                'id' => $comment->id,
                'body' => $comment->body,
                'author' => $user->name,
                'author_id' => $user->id,
                'created_at' => $comment->created_at->toIso8601String(),
                'updated_at' => $comment->updated_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Delete a comment
     *
     * This method deletes a comment. Only the comment author or users with 'delete posts' permission can delete comments.
     *
     * @group Comments
     *
     * @urlParam comment required The ID of the comment. Example: 1
     *
     * @responseField success string The success message.
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Comment $comment)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if (!$comment) {
            return response()->json(['error' => 'Comment not found'], 404);
        }

        // Check if user is the author or has delete permission
        if ($comment->creator_id !== $user->id && !$user->can('delete posts')) {
            return response()->json(['error' => 'User not allowed to delete this comment'], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => 'Comment deleted successfully',
        ], 200);
    }
}



<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Model\Post;
use DevDojo\LaravelReactions\Models\Reaction;
use Illuminate\Http\RedirectResponse;

class ReactionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }

    /**
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function react(Post $post, $reaction)
    {
        $reactionModel = Reaction::where('name', $reaction)->first();

        auth()->user()->reactTo($post, $reactionModel);

        // Reload the post to get fresh reaction counts
        $post->refresh();

        // If AJAX request, return JSON
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'total_reactions' => $post->reactions->count(),
                'user_reacted' => $post->reacted(),
                'reactions_breakdown' => [
                    'like' => [
                        'count' => $post->reactions->where('name', 'like')->count(),
                        'percentage' => $post->reactions->count() > 0
                            ? round(($post->reactions->where('name', 'like')->count() / $post->reactions->count()) * 100)
                            : 0,
                    ],
                    'love' => [
                        'count' => $post->reactions->where('name', 'love')->count(),
                        'percentage' => $post->reactions->count() > 0
                            ? round(($post->reactions->where('name', 'love')->count() / $post->reactions->count()) * 100)
                            : 0,
                    ],
                    'haha' => [
                        'count' => $post->reactions->where('name', 'haha')->count(),
                        'percentage' => $post->reactions->count() > 0
                            ? round(($post->reactions->where('name', 'haha')->count() / $post->reactions->count()) * 100)
                            : 0,
                    ],
                    'wow' => [
                        'count' => $post->reactions->where('name', 'wow')->count(),
                        'percentage' => $post->reactions->count() > 0
                            ? round(($post->reactions->where('name', 'wow')->count() / $post->reactions->count()) * 100)
                            : 0,
                    ],
                    'sad' => [
                        'count' => $post->reactions->where('name', 'sad')->count(),
                        'percentage' => $post->reactions->count() > 0
                            ? round(($post->reactions->where('name', 'sad')->count() / $post->reactions->count()) * 100)
                            : 0,
                    ],
                ],
            ]);
        }

        return redirect(url('nachrichten#'.$post->id));
    }
}

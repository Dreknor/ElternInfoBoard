<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Post;
use App\Model\User;
use Carbon\Carbon;
use DevDojo\LaravelReactions\Models\Reaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use function PHPUnit\Framework\returnArgument;

/**
 * Class NachrichtenController
 *
 * Controller for handling Nachrichten (messages) related API requests.
 */
class NachrichtenController extends Controller
{
    /**
     * index
     *
     * This method returns all Nachrichten (messages) that are not archived and have a release date in the future.
     * The result is returned as a JSON response.
     * The Nachrichten are ordered by the sticky attribute and the updated_at attribute.
     * The Nachrichten are enriched with the author, media, reactions, receipts and userRueckmeldung attributes.
     * The userRueckmeldung attribute is filtered by the current user.
     *
     * @group Nachrichten
     *
     * @response 200 {
     *   "success": true,
     *   "data": [{
     *     "id": 1,
     *     "header": "string",
     *     "news": "string",
     *     "author": "string",
     *     "sticky": boolean,
     *     "reactable": boolean,
     *     "read_receipt": boolean|string,
     *     "read_receipt_deadline": "datetime|null",
     *     "created_at": "datetime",
     *     "updated_at": "datetime",
     *     "archiv_ab": "datetime",
     *     "media": [],
     *     "reactions": {
     *       "enabled": boolean,
     *       "reactions": {"like": 0, "love": 0, "celebrate": 0}
     *     },
     *     "user_reaction": "string|null",
     *     "user_receipt": boolean,
     *     "feedback": {
     *       "type": "string|null",
     *       "has_feedback": boolean,
     *       "user_has_responded": boolean
     *     },
     *     "poll": {
     *       "has_poll": boolean,
     *       "poll_id": "integer|null",
     *       "user_has_voted": boolean
     *     },
     *     "comments": {
     *       "enabled": boolean,
     *       "count": integer
     *     }
     *   }],
     *   "available_reactions": [
     *     {
     *       "name": "like",
     *       "id":: integer
     *     },
     *     {
     *       "name": "love",
     *        "id":: integer
     *     }
     *   ],
     *   "message": "Posts retrieved successfully"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => 'User not found',
                'message' => 'Benutzer nicht gefunden'
            ], 404);
        }

        if ($user->hasPermissionTo('view all', 'web')) {
            $nachrichten = Post::query()
                ->whereDate('archiv_ab', '>', Carbon::now()->startOfDay())
                ->orderByDesc('sticky')
                ->orderByDesc('updated_at')
                ->with(['autor' => function ($query) {
                    $query->select('id', 'name');
                }])
                ->with(['media' => function ($query) {
                    return $query->select('id', 'collection_name', 'file_name', 'mime_type', 'uuid');
                }])
                ->with(['reactions' => function ($query) {
                    return $query->select('name');
                }])
                ->with(['receipts' => function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }])
                ->with(['userRueckmeldung' => function ($query) use ($user) {
                    return $query->where([
                        'users_id' => $user->id,
                    ]);
                }])

                ->get();

        } else {

            $nachrichten = $user->postsNotArchived()
                ->distinct()
                ->where('released', 1)
                ->orderByDesc('sticky')
                ->orderByDesc('updated_at')
                ->whereDate('archiv_ab', '>', $user->created_at)
                ->with(['autor' => function ($query) {
                    $query->select('id', 'name');
                }])
                ->with(['media' => function ($query) {
                    return $query->select('id', 'collection_name', 'file_name', 'mime_type', 'uuid');
                }])
                ->with(['reactions' => function ($query) {
                    return $query->select('name');
                }])
                ->with(['receipts' => function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }])
                ->with(['userRueckmeldung' => function ($query) use ($user) {
                    return $query->where([
                        'users_id' => $user->id,
                    ]);
                }])
                ->get();

            if ($user->hasPermissionTo('create posts', 'web')) {
                $eigenePosts = Post::query()
                    ->where('author', $user->id)
                    ->whereDate('archiv_ab', '>', Carbon::now()->startOfDay())
                    ->where('external', 0)
                    ->with(['autor' => function ($query) {
                        $query->select('id', 'name');
                    }])
                    ->with(['media' => function ($query) {
                        return $query->select('id', 'model_id', 'model_type', 'collection_name', 'file_name', 'mime_type', 'disk');
                    }])
                    ->with(['reactions' => function ($query) {
                        return $query->select('name');
                    }])
                    ->with(['receipts' => function ($query) use ($user) {
                        return $query->where('user_id', $user->id);
                    }])
                    ->with(['userRueckmeldung' => function ($query) use ($user) {
                        return $query->where([
                            'users_id' => $user->id,
                        ]);
                    }])
                    ->get();

                $nachrichten = $nachrichten->concat($eigenePosts);

            }
        }

        $nachrichten = $nachrichten->unique('id');

        Log::debug($nachrichten);

        // Lade alle verfügbaren Reaktionstypen aus der Datenbank
        $availableReactions = Reaction::query()
            ->select('id', 'name')
            ->get()
            ->map(function ($reaction) {
                return [
                    'name' => $reaction->name,
                    'id' => $reaction->id
                ];
            });

        $formattedNachrichten = [];

        foreach ($nachrichten as $nachricht) {
            $nachricht->author = (is_null($nachricht->autor)) ? 'Fehler bei Nachricht '.$nachricht->id : $nachricht->autor->name;
            unset($nachricht->autor);

            // Format reactions - initialisiere mit 0 für alle verfügbaren Reaktionen
            $reactions = [];
            foreach ($availableReactions as $availableReaction) {
                $reactions[$availableReaction['name']] = 0;
            }

            // Zähle die tatsächlichen Reaktionen für diesen Post
            foreach ($nachricht->getReactionsSummary() as $reaction) {
                $reactions[$reaction->name] = $reaction->count;
            }

            $nachricht->read_receipt = ($nachricht->read_receipt == true) ? '1' : false;
            $nachricht->userReceipt = (is_null($nachricht->receipts()->where('user_id', $user->id)->first())) ? false : true;

            unset($nachricht->reactions);
            $nachricht->userReaction = $nachricht->userReaction($user);

            // Structure reactions with enabled flag and reactions object
            $nachricht->reactions = [
                'enabled' => (bool) $nachricht->reactable,
                'reactions' => $reactions
            ];

            // Structure feedback information
            $feedbackInfo = [
                'type' => null,
                'has_feedback' => false,
                'user_has_responded' => false,
                'is_required' => false,
                'deadline' => null,
                'allows_multiple' => false,
            ];

            if ($nachricht->rueckmeldung) {
                $feedbackInfo['has_feedback'] = true;
                $feedbackInfo['type'] = $nachricht->rueckmeldung->type;
                $feedbackInfo['is_required'] = (bool) $nachricht->rueckmeldung->pflicht;
                $feedbackInfo['deadline'] = $nachricht->rueckmeldung->ende;
                $feedbackInfo['allows_multiple'] = (bool) $nachricht->rueckmeldung->multiple;
                $feedbackInfo['is_commentable'] = (bool) $nachricht->rueckmeldung->commentable;

                $userFeedback = $nachricht->userRueckmeldung()
                    ->where('users_id', $user->id)
                    ->first();

                $feedbackInfo['user_has_responded'] = !is_null($userFeedback);
            }

            $nachricht->feedback = $feedbackInfo;

            // Structure poll information
            $pollInfo = [
                'has_poll' => false,
                'poll_id' => null,
                'user_has_voted' => false,
            ];

            if ($nachricht->poll) {
                $pollInfo['has_poll'] = true;
                $pollInfo['poll_id'] = $nachricht->poll->id;
                $pollInfo['user_has_voted'] = $nachricht->poll->votes()->where('author_id', $user->id)->exists();
            }

            $nachricht->poll = $pollInfo;

            // Structure comment information
            $commentInfo = [
                'enabled' => ($nachricht->rueckmeldung && $nachricht->rueckmeldung->commentable),
                'count' => $nachricht->comments()->count(),
            ];

            $nachricht->comments = $commentInfo;

            // Remove redundant fields
            unset($nachricht->userRueckmeldung);
            unset($nachricht->receipts);
            unset($nachricht->rueckmeldung);

            $formattedNachrichten[] = $nachricht;
        }

        return response()->json([
            'success' => true,
            'data' => $formattedNachrichten,
            'available_reactions' => $availableReactions->toArray(),
            'message' => 'Posts retrieved successfully'
        ]);
    }

    /**
     * Store or update
     *
     * This method creates a new Reaction for a Post or updates an existing Reaction.
     * The result is returned as a JSON response.
     *
     * @group Nachrichten
     *
     * @urlParam post_id int required The ID of the post. Example: 1
     *
     * @bodyParam reaction string required The name of the reaction. Example: like
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Reaction added"
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "error": "Post not found",
     *   "message": "Der Beitrag wurde nicht gefunden"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateReaction(Request $request, Post $post)
    {

        if (! $post) {
            return response()->json([
                'success' => false,
                'error' => 'Post not found',
                'message' => 'Der Beitrag wurde nicht gefunden'
            ], 404);
        }

        $reaction = Reaction::query()->where('name', $request->reaction)->first();
        if (! $reaction) {
            return response()->json([
                'success' => false,
                'error' => 'Reaction not found',
                'message' => 'Die Reaktion wurde nicht gefunden'
            ], 404);
        }

        Log::debug('Reaktion: ' . $reaction);
        Log::debug('Post: ' . $post);
        Log::debug('oldReaction: ' . $post->getReactionsSummary());

        $user = $request->user();

        Log::debug('User '.$user->id.' reacts to post '.$post->id.' with reaction '.$reaction->name);


        $user->reactTo($post, $reaction);

        Log::debug($post->getReactionsSummary());

        return response()->json([
            'success' => true,
            'message' => 'Reaction added'
        ], 200);

    }

    /**
     * Return the specified resource.
     * This method returns a single Nachrichten (message) by its ID.
     *
     * The result is returned as a JSON response.
     *
     * @group Nachrichten
     *
     * @urlParam post required The ID of the post. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "header": "string",
     *     "news": "string",
     *     "author": "string",
     *     "sticky": boolean,
     *     "reactable": boolean,
     *     "read_receipt": boolean|string,
     *     "read_receipt_deadline": "datetime|null",
     *     "created_at": "datetime",
     *     "updated_at": "datetime",
     *     "archiv_ab": "datetime",
     *     "media": [],
     *     "reactions": {
     *       "enabled": boolean,
     *       "reactions": {"like": 0, "love": 0, "celebrate": 0}
     *     },
     *     "user_reaction": "string|null",
     *     "user_receipt": boolean,
     *     "feedback": {
     *       "type": "string|null",
     *       "has_feedback": boolean,
     *       "user_has_responded": boolean
     *     },
     *     "poll": {
     *       "has_poll": boolean,
     *       "poll_id": "integer|null",
     *       "user_has_voted": boolean
     *     },
     *     "comments": {
     *       "enabled": boolean,
     *       "count": integer
     *     }
     *   },
     *   "available_reactions": [
     *     {
     *       "name": "like",
     *       "id":: integer
     *     },
     *     {
     *       "name": "love",
     *        "id":: integer
     *     }
     *   ],
     *   "message": "Post retrieved successfully"
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "error": "Post not found",
     *   "message": "Der Beitrag wurde nicht gefunden"
     * }
     *
     * @response 403 {
     *   "success": false,
     *   "error": "User not allowed",
     *   "message": "Sie haben keine Berechtigung für diesen Beitrag"
     * }
     *
     * @param  int  $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Post $post)
    {

        if (! $post) {
            return response()->json([
                'success' => false,
                'error' => 'Post not found',
                'message' => 'Der Beitrag wurde nicht gefunden'
            ], 404);
        }

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => 'User not found',
                'message' => 'Benutzer nicht gefunden'
            ], 404);
        }

        // Check if user has access to this post
        if (! $user->hasPermissionTo('view all', 'web')) {
            if (! $post->users->contains($user)) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not allowed',
                    'message' => 'Sie haben keine Berechtigung für diesen Beitrag'
                ], 403);
            }
        }

        // Load only necessary relations with user-specific filtering
        $post->load([
            'autor' => function ($query) {
                $query->select('id', 'name');
            },
            'media' => function ($query) {
                return $query->select('id', 'collection_name', 'file_name', 'mime_type', 'uuid');
            },
            'reactions' => function ($query) {
                return $query->select('name');
            },
            'receipts' => function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            },
            'userRueckmeldung' => function ($query) use ($user) {
                return $query->where('users_id', $user->id);
            }
        ]);

        // Load all available reactions
        $availableReactions = Reaction::query()
            ->select('id', 'name')
            ->get()
            ->map(function ($reaction) {
                return [
                    'name' => $reaction->name,
                    'id' => $reaction->id
                ];
            });

        // Format author
        $post->author = (is_null($post->autor)) ? 'Fehler bei Nachricht '.$post->id : $post->autor->name;
        unset($post->autor);

        // Format reactions - initialize with 0 for all available reactions
        $reactions = [];
        foreach ($availableReactions as $availableReaction) {
            $reactions[$availableReaction['name']] = 0;
        }

        // Count actual reactions for this post
        foreach ($post->getReactionsSummary() as $reaction) {
            $reactions[$reaction->name] = $reaction->count;
        }

        $post->read_receipt = ($post->read_receipt == true) ? '1' : false;
        $post->userReceipt = (is_null($post->receipts()->where('user_id', $user->id)->first())) ? false : true;

        unset($post->reactions);
        $post->userReaction = $post->userReaction($user);

        // Structure reactions with enabled flag and reactions object
        $post->reactions = [
            'enabled' => (bool) $post->reactable,
            'reactions' => $reactions
        ];

        // Structure feedback information
        $feedbackInfo = [
            'type' => null,
            'has_feedback' => false,
            'user_has_responded' => false,
            'is_required' => false,
            'deadline' => null,
            'allows_multiple' => false,
        ];

        if ($post->rueckmeldung) {
            $feedbackInfo['has_feedback'] = true;
            $feedbackInfo['type'] = $post->rueckmeldung->type;
            $feedbackInfo['is_required'] = (bool) $post->rueckmeldung->pflicht;
            $feedbackInfo['deadline'] = $post->rueckmeldung->ende;
            $feedbackInfo['allows_multiple'] = (bool) $post->rueckmeldung->multiple;
            $feedbackInfo['is_commentable'] = (bool) $post->rueckmeldung->commentable;

            $userFeedback = $post->userRueckmeldung()
                ->where('users_id', $user->id)
                ->first();

            $feedbackInfo['user_has_responded'] = !is_null($userFeedback);
        }

        $post->feedback = $feedbackInfo;

        // Structure poll information
        $pollInfo = [
            'has_poll' => false,
            'poll_id' => null,
            'user_has_voted' => false,
        ];

        if ($post->poll) {
            $pollInfo['has_poll'] = true;
            $pollInfo['poll_id'] = $post->poll->id;
            $pollInfo['user_has_voted'] = $post->poll->votes()->where('author_id', $user->id)->exists();
        }

        $post->poll = $pollInfo;

        // Structure comment information
        $commentInfo = [
            'enabled' => ($post->rueckmeldung && $post->rueckmeldung->commentable),
            'count' => $post->comments()->count(),
        ];

        $post->comments = $commentInfo;

        // Remove redundant fields that might contain data from other users
        unset($post->userRueckmeldung);
        unset($post->receipts);
        unset($post->rueckmeldung);

        return response()->json([
            'success' => true,
            'data' => $post,
            'available_reactions' => $availableReactions->toArray(),
            'message' => 'Post retrieved successfully'
        ]);

    }
}

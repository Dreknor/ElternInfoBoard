<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Post;
use App\Model\User;
use Carbon\Carbon;
use DevDojo\LaravelReactions\Models\Reaction;
use Illuminate\Http\Request;
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
     *     "media": [{
     *       "id": integer,
     *       "uuid": "string",
     *       "collection": "string",
     *       "name": "string",
     *       "file_name": "string",
     *       "mime_type": "string",
     *       "size": integer,
     *       "order": integer,
     *       "url": "string",
     *       "url_by_id": "string"
     *     }],
     *     "reactions": {
     *       "enabled": boolean,
     *       "reactions": {"like": 0, "love": 0, "celebrate": 0}
     *     },
     *     "user_reaction": "string|null",
     *     "user_receipt": boolean,
     *     "feedback": {
     *       "type": "string|null",
     *       "has_feedback": boolean,
     *       "user_has_responded": boolean,
     *       "is_required": boolean,
     *       "is_active": boolean,
     *       "deadline": "datetime|null",
     *       "allows_multiple": boolean,
     *       "is_commentable": boolean,
     *       "text": "string|null",
     *       "max_answers": "integer|null",
     *       "options": [
     *         {
     *           "id": integer,
     *           "type": "string",
     *           "option": "string",
     *           "required": boolean
     *         }
     *       ],
     *       "user_responses": [
     *         {
     *           "id": integer,
     *           "text": "string|null",
     *           "rueckmeldung_number": "integer|null",
     *           "created_at": "datetime",
     *           "answers": [
     *             {
     *               "option_id": integer,
     *               "option_text": "string|null",
     *               "option_type": "string|null",
     *               "answer": "string|null"
     *             }
     *           ]
     *         }
     *       ]
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
                    return $query->select('id', 'model_id', 'model_type', 'collection_name', 'name', 'file_name', 'mime_type', 'size', 'uuid', 'order_column', 'disk');
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
                    ])->with('answers.option');
                }])
                ->with(['rueckmeldung.options'])

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
                    return $query->select('id', 'model_id', 'model_type', 'collection_name', 'name', 'file_name', 'mime_type', 'size', 'uuid', 'order_column', 'disk');
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
                    ])->with('answers.option');
                }])
                ->with(['rueckmeldung.options'])
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
                        return $query->select('id', 'model_id', 'model_type', 'collection_name', 'name', 'file_name', 'mime_type', 'size', 'uuid', 'order_column', 'disk');
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
                        ])->with('answers.option');
                    }])
                    ->with(['rueckmeldung.options'])
                    ->get();

                $nachrichten = $nachrichten->concat($eigenePosts);

            }
        }

        $nachrichten = $nachrichten->unique('id');

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

            // Format media with download URLs
            if ($nachricht->media) {
                $nachricht->media = $nachricht->media->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'uuid' => $media->uuid,
                        'collection' => $media->collection_name,
                        'name' => $media->name,
                        'file_name' => $media->file_name,
                        'mime_type' => $media->mime_type,
                        'size' => $media->size,
                        'order' => $media->order_column,
                        'url' => url('/api/file/' . $media->uuid),
                        'url_by_id' => url('/api/image/' . $media->id),
                    ];
                })->values()->all();
            }

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
                'is_active' => false,
                'deadline' => null,
                'allows_multiple' => false,
                'is_commentable' => false,
                'text' => null,
                'options' => [],
                'user_responses' => [],
            ];

            if ($nachricht->rueckmeldung) {
                $feedbackInfo['has_feedback'] = true;
                $feedbackInfo['type'] = $nachricht->rueckmeldung->type;
                $feedbackInfo['is_required'] = (bool) $nachricht->rueckmeldung->pflicht;
                $feedbackInfo['is_active'] = $nachricht->rueckmeldung->active;
                $feedbackInfo['deadline'] = $nachricht->rueckmeldung->ende;
                $feedbackInfo['allows_multiple'] = (bool) $nachricht->rueckmeldung->multiple;
                $feedbackInfo['is_commentable'] = (bool) $nachricht->rueckmeldung->commentable;
                $feedbackInfo['text'] = $nachricht->rueckmeldung->text;
                $feedbackInfo['max_answers'] = $nachricht->rueckmeldung->max_answers;

                // Füge Optionen hinzu (für Abfragen)
                if ($nachricht->rueckmeldung->type === 'abfrage' && $nachricht->rueckmeldung->options) {
                    $feedbackInfo['options'] = $nachricht->rueckmeldung->options->map(function ($option) {
                        return [
                            'id' => $option->id,
                            'type' => $option->type,
                            'option' => $option->option,
                            'required' => (bool) $option->required,
                        ];
                    })->toArray();
                }

                // Füge Benutzer-Antworten hinzu
                $userFeedbacks = $nachricht->userRueckmeldung()
                    ->where('users_id', $user->id)
                    ->with('answers.option')
                    ->get();

                $feedbackInfo['user_has_responded'] = $userFeedbacks->isNotEmpty();

                if ($userFeedbacks->isNotEmpty()) {
                    $feedbackInfo['user_responses'] = $userFeedbacks->map(function ($userFeedback) {
                        $response = [
                            'id' => $userFeedback->id,
                            'text' => $userFeedback->text,
                            'rueckmeldung_number' => $userFeedback->rueckmeldung_number,
                            'created_at' => $userFeedback->created_at,
                            'answers' => [],
                        ];

                        // Füge Abfrage-Antworten hinzu
                        if ($userFeedback->answers && $userFeedback->answers->isNotEmpty()) {
                            $response['answers'] = $userFeedback->answers->map(function ($answer) {
                                return [
                                    'option_id' => $answer->option_id,
                                    'option_text' => $answer->option->option ?? null,
                                    'option_type' => $answer->option->type ?? null,
                                    'answer' => $answer->answer,
                                ];
                            })->toArray();
                        }

                        return $response;
                    })->toArray();
                }
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

        $user = $request->user();
        $user->reactTo($post, $reaction);

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
     *       "user_has_responded": boolean,
     *       "is_required": boolean,
     *       "is_active": boolean,
     *       "deadline": "datetime|null",
     *       "allows_multiple": boolean,
     *       "is_commentable": boolean,
     *       "text": "string|null",
     *       "max_answers": "integer|null",
     *       "options": [
     *         {
     *           "id": integer,
     *           "type": "string",
     *           "option": "string",
     *           "required": boolean
     *         }
     *       ],
     *       "user_responses": [
     *         {
     *           "id": integer,
     *           "text": "string|null",
     *           "rueckmeldung_number": "integer|null",
     *           "created_at": "datetime",
     *           "answers": [
     *             {
     *               "option_id": integer,
     *               "option_text": "string|null",
     *               "option_type": "string|null",
     *               "answer": "string|null"
     *             }
     *           ]
     *         }
     *       ]
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
                return $query->select('id', 'model_id', 'model_type', 'collection_name', 'name', 'file_name', 'mime_type', 'size', 'uuid', 'order_column', 'disk');
            },
            'reactions' => function ($query) {
                return $query->select('name');
            },
            'receipts' => function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            },
            'userRueckmeldung' => function ($query) use ($user) {
                return $query->where('users_id', $user->id)
                    ->with('answers.option');
            },
            'rueckmeldung.options'
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

        // Format media with download URLs
        if ($post->media) {
            $post->media = $post->media->map(function ($media) {
                return [
                    'id' => $media->id,
                    'uuid' => $media->uuid,
                    'collection' => $media->collection_name,
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'order' => $media->order_column,
                    'url' => url('/api/file/' . $media->uuid),
                    'url_by_id' => url('/api/image/' . $media->id),
                ];
            })->values()->all();
        }

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
            'is_active' => false,
            'deadline' => null,
            'allows_multiple' => false,
            'is_commentable' => false,
            'text' => null,
            'options' => [],
            'user_responses' => [],
        ];

        if ($post->rueckmeldung) {
            $feedbackInfo['has_feedback'] = true;
            $feedbackInfo['type'] = $post->rueckmeldung->type;
            $feedbackInfo['is_required'] = (bool) $post->rueckmeldung->pflicht;
            $feedbackInfo['is_active'] = $post->rueckmeldung->active;
            $feedbackInfo['deadline'] = $post->rueckmeldung->ende;
            $feedbackInfo['allows_multiple'] = (bool) $post->rueckmeldung->multiple;
            $feedbackInfo['is_commentable'] = (bool) $post->rueckmeldung->commentable;
            $feedbackInfo['text'] = $post->rueckmeldung->text;
            $feedbackInfo['max_answers'] = $post->rueckmeldung->max_answers;

            // Füge Optionen hinzu (für Abfragen)
            if ($post->rueckmeldung->type === 'abfrage' && $post->rueckmeldung->options) {
                $feedbackInfo['options'] = $post->rueckmeldung->options->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'type' => $option->type,
                        'option' => $option->option,
                        'required' => (bool) $option->required,
                    ];
                })->toArray();
            }

            // Füge Benutzer-Antworten hinzu
            $userFeedbacks = $post->userRueckmeldung()
                ->where('users_id', $user->id)
                ->with('answers.option')
                ->get();

            $feedbackInfo['user_has_responded'] = $userFeedbacks->isNotEmpty();

            if ($userFeedbacks->isNotEmpty()) {
                $feedbackInfo['user_responses'] = $userFeedbacks->map(function ($userFeedback) {
                    $response = [
                        'id' => $userFeedback->id,
                        'text' => $userFeedback->text,
                        'rueckmeldung_number' => $userFeedback->rueckmeldung_number,
                        'created_at' => $userFeedback->created_at,
                        'answers' => [],
                    ];

                    // Füge Abfrage-Antworten hinzu
                    if ($userFeedback->answers && $userFeedback->answers->isNotEmpty()) {
                        $response['answers'] = $userFeedback->answers->map(function ($answer) {
                            return [
                                'option_id' => $answer->option_id,
                                'option_text' => $answer->option->option ?? null,
                                'option_type' => $answer->option->type ?? null,
                                'answer' => $answer->answer,
                            ];
                        })->toArray();
                    }

                    return $response;
                })->toArray();
            }
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

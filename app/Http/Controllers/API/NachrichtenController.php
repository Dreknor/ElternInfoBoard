<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Post;
use Carbon\Carbon;
use DevDojo\LaravelReactions\Models\Reaction;
use Illuminate\Http\Request;
use App\Model\User;
use Illuminate\Support\Facades\Log;

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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {

        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if ( $user->hasPermissionTo('view all', 'web')) {
            $nachrichten = Post::query()
                ->select([
                    "id",
                    "header",
                    "news",
                    "read_receipt",
                    "sticky",
                    "reactable",
                    "updated_at",
                    "author",
                    "archiv_ab",
                    "type",
                    "external",
                ])
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
                ->with(['userRueckmeldung' => function ($query) use ($user)  {
                    return $query->where([
                        'users_id' => $user->id,
                    ]);
                }])

                ->get();


        } else {


            $nachrichten = $user->postsNotArchived()
                ->distinct()
                ->select([
                    "id",
                    "header",
                    "news",
                    "read_receipt",
                    "sticky",
                    "reactable",
                    "updated_at",
                    "author",
                    "archiv_ab",
                    "type",
                    "external",
                ])
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



        $reactions_collection = Reaction::query()->select('name')->get()->toArray();

        foreach ($nachrichten as $nachricht) {
            $nachricht->author = (is_null($nachricht->autor)) ? 'Fehler bei Nachricht '.$nachricht->id : $nachricht->autor->name;
            unset($nachricht->autor);

            $reactions = array_fill_keys(array_column($reactions_collection, 'name'),0);
            foreach ($nachricht->getReactionsSummary() as $reaction) {
                $reactions[$reaction->name] = $reaction->count;
            }

            $nachricht->read_receipt = ($nachricht->read_receipt == true) ? '1' : false;

            $nachricht->userReceipt = (is_null($nachricht->receipts()->where('user_id', $user->id)->first())) ? false : true;

            unset($nachricht->reactions);
            $nachricht->userReaction = $nachricht->userReaction($user);
            $nachricht->reactions = $reactions;

        }


        return response()->json($nachrichten);
    }

    /**
     * Store or update
     *
     * This method creates a new Reaction for a Post or updates an existing Reaction.
     * The result is returned as a JSON response.
     *
     * @group Nachrichten
     *
     *
     * @param  \Illuminate\Http\Request  $request
     * @urlParam post_id int required The ID of the post. Example: 1
     *
     * @param  \Illuminate\Http\Request  $request
     * @bodyParam reaction string required The name of the reaction. Example: like
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateReaction(Request $request, Post $post)
    {

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        $reaction = Reaction::query()->where('name', $request->reaction)->first();
        if (!$reaction) {
            return response()->json(['error' => 'Reaction not found'], 404);
        }

        $user = $request->user();

        $user->reactTo($post, $reaction);

        return response()->json(['success' => 'Reaction added'], 200);

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
     * @param  \Illuminate\Http\Request  $request
     *
     * @param  int  $post
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Post $post)
    {
        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }


        if (!$post->users->contains($user)) {
            return response()->json(['error' => 'User not allowed'], 403);
        }

        return response()->json($post);

    }

}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Post;
use Carbon\Carbon;
use DevDojo\LaravelReactions\Models\Reaction;
use Illuminate\Http\Request;
use App\Model\User;


class NachrichtenController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {

        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if (! $user->hasPermissionTo('view all', 'web')) {
            $nachrichten = $user->postsNotArchived()
                ->distinct()
                ->where('external', 0)
                ->orderByDesc('sticky')
                ->orderByDesc('updated_at')
                ->whereDate('archiv_ab', '>', $user->created_at)
                ->with(['autor' => function ($query) {
                    $query->select('id', 'name');
                }])
                ->with(['media' => function ($query) {
                    return $query->select('id', 'model_id', 'model_type', 'collection_name', 'file_name', 'mime_type', 'disk', 'uuid');
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
                    ->with(['userRueckmeldung' => function ($query) use ($user)  {
                        return $query->where([
                            'users_id' => $user->id,
                        ]);
                    }])
                    ->get();
                $nachrichten = $nachrichten->concat($eigenePosts);
            }
        } else {

            $nachrichten = Post::whereDate('archiv_ab', '>', Carbon::now()->startOfDay())
                ->where('external', 0)
                ->where('released', 1)
                ->orderByDesc('sticky')
                ->orderByDesc('updated_at')
                ->with(['autor' => function ($query) {
                    $query->select('id', 'name');
                }])
                ->with(['media' => function ($query) {
                    return $query->select('id', 'model_id', 'model_type', 'collection_name', 'file_name', 'mime_type', 'disk', 'uuid');
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

            $nachricht->userReceipt = (is_null($nachricht->receipts()->where('user_id', $user->id)->first())) ? false : true;

            unset($nachricht->reactions);
            $nachricht->userReaction = $nachricht->userReaction($user);
            $nachricht->reactions = $reactions;
        }


        return response()->json($nachrichten);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateReaction(Request $request, $postID)
    {


        $post = Post::query()->find($postID);
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\AbfrageAntworten;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use App\Model\User;
use App\Model\UserRueckmeldungen;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;


/**
 * Class AbfragenController
 * Controller for handling abfragen related API requests.
 */
class AbfragenController extends Controller
{
    /**
     * AbfragenController constructor.
     * Apply authentication middleware.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get fields for a post
     *
     * Get the fields for the post with the given id
     *
     *  @group Rückmeldungen
     *
     * @responseField fields array The fields
     * @responseField rueckmeldung object The rueckmeldung
     *
     * @urlParam post_id required The id of the post
     *
     *
     * @param $post_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFields($post_id){

        $post = Post::query()->where('id', $post_id)->firstOrFail();

        if ($post == null)
        {
            return response()->json(['success' => false, 'message' => 'Post not found']);
        }

        if ($post->groups->intersect(auth()->user()->groups)->count() == 0)
        {
            return response()->json(['success' => false, 'message' => 'You are not allowed to see this post']);
        }


        $rueckmeldung = Rueckmeldungen::query()
            ->where('post_id', $post_id)
            ->where('type', 'abfrage')
            ->first();

        return response()->json([
            'success' => true,
            'fields' => $rueckmeldung->options,
            'rueckmeldung' => $rueckmeldung,
            ]);

    }

    /**
     * Store answer
     *
     * Store the answer for the post with the given id
     *
     * @group Rückmeldungen
     *
     * bodyParam data array required the data to store. The data must be an array of objects with the following structure of getFields response.
     *
     *
     *
     * @urlParam post required The id of the post
     *
     * @param Request $request
     * @param $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeAnswer(Request $request, $post)
    {


        $request->validate([
            'data' => 'required',
        ]);

        $post = Post::query()->where('id', $post)->firstOrFail();

        if ($post == null)
        {
            return response()->json(['success' => false, 'message' => 'Post not found']);
        }

        if ($post->groups->intersect(request()->user()->groups)->count() == 0)
        {
            return response()->json(['success' => false, 'message' => 'You are not allowed to answer this post']);
        }


        $rueckmeldung = $post->rueckmeldung;

        if ($rueckmeldung == null)
        {
            return response()->json(['success' => false, 'message' => 'No abfrage found']);
        }

        if ($rueckmeldung->type != 'abfrage')
        {
            return response()->json(['success' => false, 'message' => 'Invalid abfrage type']);
        }

        $userRueckmeldung = UserRueckmeldungen::query()
            ->where('post_id', $post->id)
            ->where('users_id', request()->user()->id)
            ->first();

        if ($rueckmeldung->multiple == 1 or $userRueckmeldung == null)
        {
            $userRueckmeldung = UserRueckmeldungen::create([
                'post_id' => $post->id,
                'users_id' => request()->user()->id,
                'text' => ' ',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $data = [];


            foreach ($request->data as $value) {


                if (is_array($value) && count($value) == 2)
                {
                    if (!is_numeric($value['id']))
                    {
                        return response()->json(['success' => false, 'message' => 'Invalid data']);
                    }

                    $data[] = [
                        'rueckmeldung_id' => $userRueckmeldung->id,
                        'user_id' => request()->user()->id,
                        'option_id' => $value['id'] ,
                        'answer' => $value['value'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                }
                else
                {
                    return response()->json(['success' => false, 'message' => 'Invalid data']);
                }


            }

        } else {
            $userRueckmeldung = UserRueckmeldungen::updateOrCreate([
                'post_id' => $post->id,
                'users_id' => request()->user()->id],
                [
                'text' => ' ',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            AbfrageAntworten::query()->where('rueckmeldung_id', $userRueckmeldung->id)->delete();

            $data = [];


            foreach ($request->data as $value) {


                if (is_array($value) && count($value) == 2)
                {
                    if (!is_numeric($value['id']))
                    {
                        return response()->json(['success' => false, 'message' => 'Invalid data']);
                    }

                    $data[] = [
                        'rueckmeldung_id' => $userRueckmeldung->id,
                        'user_id' => request()->user()->id,
                        'option_id' => $value['id'] ,
                        'answer' => $value['value'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                }
                else
                {
                    return response()->json(['success' => false, 'message' => 'Invalid data']);
                }


            }

        }

        if (count($data) == 0)
        {
            $rueckmeldung->delete();
            return response()->json(['success' => false, 'message' => 'Invalid data']);
        }

        try {
            AbfrageAntworten::insert($data);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['success' => false, 'message' => 'Error saving data']);
        }



        return response()->json(['success' => true, 'message' => 'Antwort gespeichert']);

    }
}

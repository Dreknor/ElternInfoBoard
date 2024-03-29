<?php

namespace App\Http\Controllers;

use App\Model\Post;
use DevDojo\LaravelReactions\Models\Reaction;
use Illuminate\Http\RedirectResponse;

class ReactionController extends Controller
{
    /**
     * Authentifcation required
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Post $post
     * @param $reaction
     * @return RedirectResponse
     */
    public function react(Post $post, $reaction)
    {
        $reaction = Reaction::where('name', $reaction)->first();

        auth()->user()->reactTo($post, $reaction);

        return redirect()->to(url()->previous().'#'.$post->id);
    }
}

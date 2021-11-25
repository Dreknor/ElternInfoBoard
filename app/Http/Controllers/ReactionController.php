<?php

namespace App\Http\Controllers;

use App\Model\Post;
use DevDojo\LaravelReactions\Models\Reaction;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function react(Post $post, $reaction)
    {
        $reaction = Reaction::where('name', $reaction)->first();

        auth()->user()->reactTo($post, $reaction);

        return redirect()->to(url()->previous() . '#' . $post->id);
    }
}

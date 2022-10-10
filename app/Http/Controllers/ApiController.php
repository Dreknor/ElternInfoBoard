<?php

namespace App\Http\Controllers;

use App\Model\Group;
use App\Model\Post;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ApiController extends Controller
{
    public function kioskThemes()
    {
        $Nachrichten = new Collection();

        /*
                $Gruppen = Group::where('protected', 0)->with(['posts' => function ($query){
                    $query->whereDate('posts.archiv_ab', '>', Carbon::now()->startOfDay());
                }])->get();

                foreach ($Gruppen as $Gruppe){
                    $Nachrichten = $Nachrichten->concat($Gruppe->posts);
                }['id','posts.header','posts.id','posts.updated_at']
        */

        $Nachrichten = Post::whereDate('posts.archiv_ab', '>', Carbon::now()->startOfDay())->with(['groups' => function ($query) {
            $query->where('groups.protected', 0);
        }])->get(['id', 'header', 'updated_at']);

        //dd($Nachrichten);

        return $Nachrichten->makeHidden(['groups', 'rueckmeldung'])->toJson();
    }
}

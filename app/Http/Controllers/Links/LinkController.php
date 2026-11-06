<?php

namespace App\Http\Controllers\Links;

use App\Http\Controllers\Controller;
use App\Model\Link;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Modul „Links“: Übersicht der für den Nutzer sichtbaren Links. */
class LinkController extends Controller
{
    public function index(Request $request): View
    {
        $links = Link::query()->visibleTo($request->user())->ordered()->get();

        return view('links.index', [
            'links' => $links,
            'categories' => Link::grouped($links),
        ]);
    }
}

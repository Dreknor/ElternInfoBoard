<?php

namespace App\Http\Controllers;

use App\Services\HelpService;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function __construct(protected HelpService $help) {}

    public function index()
    {
        $grouped = $this->help->grouped();

        return view('help.index', [
            'grouped' => $grouped,
            'help'    => $this->help,
        ]);
    }

    public function show(string $slug, Request $request)
    {
        $topic = $this->help->find($slug);

        abort_if($topic === null, 404, 'Hilfe-Thema nicht gefunden oder kein Zugriff.');

        if (! empty($topic['site_id'])) {
            return redirect('sites/show/'.$topic['site_id']);
        }

        return view('help.show', [
            'topic'   => $topic,
            'content' => $this->help->renderContent($topic),
            'related' => $this->help->availableTopics()
                ->where('group', $topic['group'])
                ->where('slug', '!=', $topic['slug'])
                ->take(5),
        ]);
    }
}

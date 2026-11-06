<?php

namespace App\Http\Controllers\Links;

use App\Http\Controllers\Controller;
use App\Http\Requests\Links\LinkRequest;
use App\Model\Group;
use App\Model\Link;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Modul „Links“: Verwaltung (Recht „edit links“). */
class LinkAdminController extends Controller
{
    public function index(): View
    {
        $links = Link::query()->with('groups:id,name')->ordered()->get();

        return view('links.admin.index', [
            'links' => $links,
            'categories' => Link::grouped($links),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Link(['is_active' => true, 'for_all' => true]));
    }

    public function edit(Link $link): View
    {
        return $this->form($link->load('groups'));
    }

    public function store(LinkRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $link = Link::create($request->linkData() + [
                'position' => (int) Link::max('position') + 1,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
            $link->groups()->sync($request->groupIds());
        });

        return redirect()->route('links.admin.index')->with([
            'type' => 'success',
            'Meldung' => 'Link wurde angelegt.',
        ]);
    }

    public function update(LinkRequest $request, Link $link): RedirectResponse
    {
        DB::transaction(function () use ($request, $link) {
            $link->update($request->linkData() + ['updated_by' => $request->user()->id]);
            $link->groups()->sync($request->groupIds());
        });

        return redirect()->route('links.admin.index')->with([
            'type' => 'success',
            'Meldung' => 'Link wurde gespeichert.',
        ]);
    }

    public function destroy(Link $link): RedirectResponse
    {
        $link->delete();

        return redirect()->route('links.admin.index')->with([
            'type' => 'success',
            'Meldung' => 'Link wurde gelöscht.',
        ]);
    }

    /** Schnell (de)aktivieren, ohne das Formular zu öffnen. */
    public function toggle(Request $request, Link $link): RedirectResponse
    {
        $link->update(['is_active' => ! $link->is_active, 'updated_by' => $request->user()->id]);

        return redirect()->route('links.admin.index')->with([
            'type' => 'success',
            'Meldung' => $link->is_active ? 'Link ist jetzt sichtbar.' : 'Link ist jetzt ausgeblendet.',
        ]);
    }

    /** Einen Link innerhalb seiner Kategorie nach oben oder unten verschieben. */
    public function move(Request $request, Link $link): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        DB::transaction(function () use ($link, $direction) {
            $all = Link::query()->ordered()->lockForUpdate()->get(['id', 'category']);
            $ids = $all->pluck('id')->all();

            $siblings = $all->filter(fn (Link $l) => (string) $l->category === (string) $link->category)->pluck('id')->values()->all();
            $pos = array_search($link->id, $siblings, true);
            $neighbour = $siblings[$direction === 'up' ? $pos - 1 : $pos + 1] ?? null;

            if ($pos !== false && $neighbour !== null) {
                $a = array_search($link->id, $ids, true);
                $b = array_search($neighbour, $ids, true);
                [$ids[$a], $ids[$b]] = [$ids[$b], $ids[$a]];
            }

            // Positionen lückenlos neu vergeben, damit gleiche Werte das Tauschen nicht blockieren.
            foreach ($ids as $position => $id) {
                Link::whereKey($id)->where('position', '!=', $position)->update(['position' => $position]);
            }
        });

        return redirect()->route('links.admin.index');
    }

    private function form(Link $link): View
    {
        return view('links.admin.form', [
            'link' => $link,
            'gruppen' => Group::active()->get(),
            'categories' => Link::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
        ]);
    }
}

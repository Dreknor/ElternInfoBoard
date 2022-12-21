<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateChangelogRequest;
use App\Model\Changelog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;


class ChangelogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        $changelogs = Changelog::orderByDesc('updated_at')->paginate(5);

        return view('changelog.index', [
            'changelogs' => $changelogs,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return RedirectResponse|View
     */
    public function create(Request $request)
    {
        if ($request->user()->can('add changelog')) {
            return view('changelog.create');
        } else {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateChangelogRequest $request
     * @return RedirectResponse
     */
    public function store(CreateChangelogRequest $request)
    {
        $changelog = new Changelog($request->all());
        $changelog->save();

        if ($changelog->changeSettings) {
            DB::table('users')->update([
                'changeSettings' => 1,
            ]);
        }

        return redirect()->to(url('changelog'))->with([
            'type' => 'success',
            'Meldung' => 'Changelog angelegt',
        ]);
    }
}

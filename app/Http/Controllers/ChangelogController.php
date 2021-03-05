<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateChangelogRequest;
use App\Model\Changelog;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChangelogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $changelogs = Changelog::orderByDesc('updated_at')->paginate(5);

        return view('changelog.index', [
            'changelogs'    => $changelogs,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function create(Request $request)
    {
        if ($request->user()->can('add changelog')) {
            return view('changelog.create');
        } else {
            return redirect()->back()->with([
                'type'  => 'danger',
                'Meldung'   => 'Berechtigung fehlt',
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(CreateChangelogRequest $request)
    {
        $changelog = new Changelog($request->all());
        $changelog->save();

        if ($changelog->changeSettings) {
            DB::table('users')->update([
                'changeSettings'    => 1,
            ]);
        }

        return redirect(url('changelog'))->with([
           'type'   => 'success',
           'Meldung'=> 'Changelog angelegt',
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Model\Changelog  $changelog
     * @return \Illuminate\Http\Response
     */
    public function show(Changelog $changelog)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\Changelog  $changelog
     * @return \Illuminate\Http\Response
     */
    public function edit(CreateChangelogRequest $changelog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Changelog  $changelog
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Changelog $changelog)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\Changelog  $changelog
     * @return \Illuminate\Http\Response
     */
    public function destroy(Changelog $changelog)
    {
        //
    }
}

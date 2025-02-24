<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReinigsungsTaskRequest;
use App\Model\ReinigungsTask;
use Illuminate\Http\RedirectResponse;

class ReinigungsTaskController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param ReinigsungsTaskRequest $request
     * @return RedirectResponse
     */
    public function store(ReinigsungsTaskRequest $request)
    {
        $task = new ReinigungsTask($request->validated());
        $task->save();

        return redirect()->back()->with([
            'Meldung' => 'Aufgabe gespeichert.',
            'type' => 'success',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ReinigungsTask $reinigungsTask
     * @return RedirectResponse
     */
    public function destroy(ReinigungsTask $task)
    {
        $task->delete();

        return redirect()->back()->with([
            'Meldung' => 'Aufgabe gelöscht.',
            'type' => 'success',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReinigsungsTaskRequest;
use App\Model\ReinigungsTask;

class ReinigungsTaskController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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
     * @param  \App\Model\ReinigungsTask  $reinigungsTask
     * @return \Illuminate\Http\Response
     */
    public function destroy(ReinigungsTask $reinigungsTask)
    {
        $reinigungsTask->delete();

        return redirect()->back()->with([
            'Meldung' => 'Aufgabe gelöscht.',
            'type' => 'success',
        ]);
    }
}

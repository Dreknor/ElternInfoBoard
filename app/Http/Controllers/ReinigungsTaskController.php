<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteReinigungsTaskRequest;
use App\Http\Requests\ReinigsungsTaskRequest;
use App\Model\ReinigungsTask;
use Illuminate\Http\RedirectResponse;

class ReinigungsTaskController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
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
     * @param  DeleteReinigungsTaskRequest  $request
     * @return RedirectResponse
     */
    public function destroy(DeleteReinigungsTaskRequest $request)
    {
        if (!$request->has('task_id')) {
            return redirect()->back()->with([
                'Meldung' => 'Aufgabe konnte nicht gelöscht werden.',
                'type' => 'error',
            ]);
        }

        if (!auth()->user()->can('edit reinigung')) {
            return redirect()->back()->with([
                'Meldung' => 'Sie haben keine Berechtigung, diese Aufgabe zu löschen.',
                'type' => 'error',
            ]);

        }
        $task = ReinigungsTask::find($request->task_id);
        $task->delete();

        return redirect()->back()->with([
            'Meldung' => 'Aufgabe gelöscht.',
            'type' => 'success',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Model\ElternratTask;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Spatie\Permission\Models\Role;

class ElternratTaskController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            ['permission:view elternrat'],
        ];
    }

    /**
     * Display tasks
     */
    public function index()
    {
        $tasks = ElternratTask::with(['assignedUser', 'creator'])
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
            ->orderBy('due_date', 'asc')
            ->paginate(20);

        $openTasks = ElternratTask::open()->count();
        $inProgressTasks = ElternratTask::inProgress()->count();
        $completedTasks = ElternratTask::completed()->count();
        $overdueTasks = ElternratTask::overdue()->count();

        // Get elternrat users for assignment
        $elternratRole = Role::findByName('Elternrat');
        $users = $elternratRole->users;

        return view('elternrat.tasks.index', compact(
            'tasks',
            'openTasks',
            'inProgressTasks',
            'completedTasks',
            'overdueTasks',
            'users'
        ));
    }

    /**
     * Store new task
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
        ]);

        ElternratTask::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'status' => 'open',
        ]);

        return back()->with([
            'type' => 'success',
            'meldung' => 'Aufgabe erstellt',
        ]);
    }

    /**
     * Update task status
     */
    public function updateStatus(Request $request, ElternratTask $task)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,completed',
        ]);

        if ($validated['status'] === 'completed') {
            $task->markAsCompleted();
        } else {
            $task->update($validated);
        }

        return back()->with([
            'type' => 'success',
            'meldung' => 'Status aktualisiert',
        ]);
    }

    /**
     * Delete task
     */
    public function destroy(ElternratTask $task)
    {
        if (auth()->user()->can('delete elternrat file') || $task->created_by === auth()->id()) {
            $task->delete();

            return back()->with([
                'type' => 'success',
                'meldung' => 'Aufgabe gelöscht',
            ]);
        }

        return back()->with([
            'type' => 'danger',
            'meldung' => 'Keine Berechtigung',
        ]);
    }
}

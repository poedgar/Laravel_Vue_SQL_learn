<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. Start an Eloquent query builder scoped to the logged-in user
        $query = $request->user()->tasks();

        // 2. Conditionally apply a search filter if present
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->input('search') . '%');
        }

        // 3. Conditionally apply a status filter (all, completed, pending)
        if ($request->filled('status')) {
            if ($request->input('status') === 'completed') {
                $query->where('is_completed', true);
            } elseif ($request->input('status') === 'pending') {
                $query->where('is_completed', false);
            }
        }

        // 4. Return the filtered dataset along with current filter values back to Vue
        // withQueryString() ensures our existing search & status filters stay attached when we change pages
        return Inertia::render('Tasks/Index', [
            'tasks' => $query->latest()->paginate(1)->withQueryString(),
            'filters' => $request->only(['search', 'status']), // Keeps input values synchronized in the UI
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        // Create the task directly attached to the logged-in user context
        $request->user()->tasks()->create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Task $task)
    {
        // Inline authorization gate: throws a 403 Forbidden if the user doesn't own it
        Gate::authorize('modify', $task);

        $task->update([
            'is_completed' => !$task->is_completed,
        ]);

        return redirect()->back();
    }

    public function destroy(Task $task)
    {
        Gate::authorize('modify', $task);

        $task->delete();

        return redirect()->back();
    }
}

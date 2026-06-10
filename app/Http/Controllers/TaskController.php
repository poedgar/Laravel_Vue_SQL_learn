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
        // SQLite pulls ONLY tasks belonging to this logged-in account
        return Inertia::render('Tasks/Index', [
            'tasks' => $request->user()->tasks()->latest()->get()
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
            'is_completed' => !$task->is_completed
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
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Store a newly created polymorphic comment in database storage.
     */
    public function store(Request $request)
    {
        // 1. Validate the basic incoming payload requirements
        $validated = $request->validate([
            'body' => 'required|string|min:2|max:1000',
            'commentable_id' => 'required|integer',
            'commentable_type' => 'required|string|in:task,project', // strict white-list parameters
        ]);

        // 2. Map incoming string handles to fully qualified Eloquent class pathways
        $morphMap = [
            'task' => \App\Models\Task::class,
            // 'project' => \App\Models\Project::class, // Ready to scale here later!
        ];

        $modelClass = $morphMap[$validated['commentable_type']];

        // 3. Look up the parent model row instance in SQLite
        // (This also implicitly throws a 404 error if someone passes an invalid ID)
        $parentModel = $modelClass::findOrFail($validated['commentable_id']);

        // 4. Secure checking: If it's a task, make sure the current user owns it
        if ($validated['commentable_type'] === 'task' && $parentModel->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized comment action on private record models.');
        }

        // 5. Save the polymorphic relation through the parent's morphMany relationship link
        $parentModel->comments()->create([
            'body' => $validated['body'],
            'user_id' => $request->user()->id, // Mark the current authenticated author
        ]);

        return redirect()->back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
            'due_at' => ['nullable', 'date'],
        ]);

        Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? auth()->id() ?? 1,
            'created_by' => auth()->id() ?? 1,
            'priority' => $validated['priority'],
            'status' => 'pending',
            'due_at' => $validated['due_at'] ?? now()->addDays(2),
        ]);

        return redirect()->back()->with('success', 'Task created successfully.');
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,in_progress,hold,completed,rework,verified,cancelled'],
        ]);

        $task->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'completed' ? now() : null,
        ]);

        return redirect()->back()->with('success', 'Task status updated.');
    }
}

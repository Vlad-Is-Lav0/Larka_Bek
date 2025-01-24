<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        return response()->json(Task::all());
    }

    public function store(Request $request)
    {

            $validated = $request->validate([
                'description' => 'required|string',
                'is_completed' => 'nullable|boolean',
            ]);
    
            $task = Task::create($validated);
    
            return response()->json($task, 201);

    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'description' => 'nullable|string',
            'is_completed' => 'nullable|boolean',
        ]);

        $task = Task::findOrFail($id);
        $task->update($validated);

        return response()->json($task);
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();

        return response()->json(null, 204);
    }
}

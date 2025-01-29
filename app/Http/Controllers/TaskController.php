<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        return response()->json(['data'=>Task::all()]);
    }

    public function show($id) {
        return Task::findOrFail($id);
    }

    public function store(Request $request) {
        $task = new Task();
        $task->description = $request->description;
        $task->is_completed = $request->is_completed;
        $task->save();
        
        return response()->json($task, 201); // Возвращаем созданную задачу
    }

    public function update(Request $request, $id) {
        $task = Task::findOrFail($id);
        $task->description = $request->description;
        $task->is_completed = $request->is_completed;
        $task->save();
        
        return response()->json($task); // Возвращаем обновленную задачу
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();

        return response()->json(null, 204);
    }
}

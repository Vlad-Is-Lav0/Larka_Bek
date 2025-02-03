<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\MainSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Client\MoySkladClient;
use Dflydev\DotAccessData\Data;

class TaskController extends Controller
{
    private $msClient;

    public function __construct()
    {
        $settings = MainSettings::first(); //первую запись из таблицы
        if (!$settings) {
            abort(500, 'Основные настройки не найдены');
        }
        $this->msClient = new MoySkladClient($settings->ms_token, $settings->accountId);//передача токена и id
    }

    public function index()
    {
        // Получаем задачи из локальной базы
        $tasks = Task::all();
        // Получаем задачи из МойСклад
        $msTasks = $this->msClient->getTasks();

        // Преобразуем задачи из МойСклад в коллекцию с нужными полями
        $tasksCollection = $tasks->mapWithKeys(function ($task) {
            return [$task->ms_uuid => [
                'id' => $task->ms_uuid,
                'description' => $task->description,
                'is_completed' => (bool) ($task->is_completed ?? false),
                'created_at' => $task->created_at,
            ]];
        });
    
        // Преобразуем задачи из МойСклад в коллекцию
        $msTasksCollection = collect($msTasks['rows'])->mapWithKeys(function ($task) {
            return [$task['id'] => [
                'id' => $task['id'],
                'description' => $task['description'] ?? 'Описание отсутствует',
                'is_completed' => (bool) ($task['done'] ?? false),
                'created_at' => $task['created'] ?? now(),
            ]];
        });
    
        // Объединяем данные (локальные задачи приоритетнее, если id совпадают)
        $mergedTasks = $msTasksCollection->merge($tasksCollection)->values();
    
        return response()->json(['data' => $mergedTasks]);
    }

    public function show($id)
    {
        return Task::findOrFail($id);
    }

    public function store(Request $request)
    {
        $employees = $this->msClient->getEmployees();
        if (empty($employees['rows'])) {
            return response()->json(['error' => 'No employees found'], 400);
        }

        $firstEmployee = $employees['rows'][0];

        // Получаем значение параметра is_completed из запроса
        $isCompleted = filter_var($request->input('is_completed', false), FILTER_VALIDATE_BOOLEAN);

        // Создаем задачу
        $taskData = [
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'done' => $isCompleted, // Отправляем параметр 'done' как true или false
            'assignee' => [
                'meta' => [
                    'href' => $firstEmployee['meta']['href'],
                    'type' => 'employee',
                    'mediaType' => 'application/json',
                ],
            ],
        ];

        $msTask = $this->msClient->createTask($taskData);

        if ($msTask) {
            $task = new Task();
            $task->ms_uuid = $msTask['id'];
            $task->description = $taskData['description'];
            $task->is_completed = (int) $isCompleted; // Сохраняем статус выполнения в локальной БД
            $task->created_at = $msTask['created'] ?? now();
            $task->save();

            return response()->json($task, 201);
        }

        return response()->json(['error' => 'Failed to create task'], 500);
    }

    public function update(Request $request, $id)
    {
        // Проверяем, есть ли задача в локальной базе
        $task = Task::where('ms_uuid', $id)->first();
        
        if (!$task) {
            return response()->json(['error' => 'Task not found in local database'], 404);
        }

        // Обновляем задачу в локальной базе
        $task->description = $request->input('description');
        $task->is_completed = (int) filter_var($request->input('is_completed', false), FILTER_VALIDATE_BOOLEAN);
        $task->save();

        // Обновляем задачу в МойСклад
        $taskData = [
            'description' => $request->input('description'),
            'done' => filter_var($request->input('is_completed', false), FILTER_VALIDATE_BOOLEAN),
        ];

        $updatedTask = $this->msClient->updateTask($id, $taskData);

        dd('updatedTask');
        if ($updatedTask) {
            // Возвращаем обновленные данные, включая локально обновленную задачу
            $task->description = $updatedTask['description'];
            $task->is_completed = $updatedTask['done'];
            $task->save();

            return response()->json([
                'id' => $task->ms_uuid,
                'description' => $task->description,
                'is_completed' => $task->is_completed,
                'updated_at' => $task->updated_at,
            ]);
    }

    return response()->json(['error' => 'Failed to update task in MoySklad'], 500);
    }

    public function destroy($id)
    {
        // Удаляем из локальной базы
        $task = Task::where('ms_uuid', $id)->first();
        if ($task) {
            $task->delete();
        }

        // Удаляем из МойСклад
        $deleted = $this->msClient->deleteTask($id);

        if ($deleted) {
            return response()->json(null, 204);
        }

        return response()->json(['error' => 'Failed to delete task'], 500);
    }
}

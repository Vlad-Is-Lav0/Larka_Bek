<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\MainSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Client\MoySkladClient;
use Carbon\Carbon;

class TaskController extends Controller
{
    private $msClient;

    public function __construct()
    {
        $settings = MainSettings::first();
        if (!$settings) {
            abort(500, 'Основные настройки не найдены');
        }
        $this->msClient = new MoySkladClient($settings->ms_token, $settings->accountId);
    }

    public function index()
    {
        $tasks = Task::all();
        $msTasks = $this->msClient->getTasks();

        $msTasksCollection = collect($msTasks['rows'])->mapWithKeys(function ($task) {
            return [$task['id'] => [
                'id' => $task['id'],
                'description' => $task['description'] ?? 'Описание отсутствует',
                'is_completed' => (bool) ($task['done'] ?? false),
                'created_at' => Carbon::parse($task['created'] ?? now()),
                'updated_at' => Carbon::parse($task['updated'] ?? now()),
            ]];
        });

        foreach ($msTasksCollection as $taskId => $taskData) {
            Task::updateOrCreate(
                ['ms_uuid' => $taskId], 
                [
                    'description' => $taskData['description'],
                    'is_completed' => $taskData['is_completed'],
                    'created_at' => $taskData['created_at'],
                    'updated_at' => $taskData['updated_at'],
                ]
            );
        }

        $updatedTasks = Task::all();
        return response()->json(['data' => $updatedTasks]);
    }

    public function show($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        return response()->json($task);
    }


    public function store(Request $request)
    {
        $employees = $this->msClient->getEmployees();
        if (empty($employees['rows'])) {
            return response()->json(['error' => 'No employees found'], 400);
        }

        $firstEmployee = $employees['rows'][0];
        $isCompleted = filter_var($request->input('is_completed', false), FILTER_VALIDATE_BOOLEAN);

        $taskData = [
            'description' => $request->input('description'),
            'done' => $isCompleted,
            'assignee' => [
                'meta' => $firstEmployee['meta'],
            ],
        ];

        $msTask = $this->msClient->createTask($taskData);

        if ($msTask) {
            $task = Task::create([
                'ms_uuid' => $msTask['id'],
                'description' => $taskData['description'],
                'is_completed' => (int) $isCompleted,
                'created_at' => Carbon::parse($msTask['created'] ?? now()),
            ]);
            return response()->json($task, 201);
        }

        return response()->json(['error' => 'Failed to create task'], 500);
    }

    public function update(Request $request, $id)
{
    $task = Task::find($id);

    if (!$task) {
        Log::error("Ошибка: Задача с id {$id} не найдена в локальной базе.");
        return response()->json(['error' => 'Task not found'], 404);
    }

    $ms_uuid = $task->ms_uuid;
    Log::info("Редактирование задачи: ID = {$id}, MS_UUID = {$ms_uuid}");

    if (!$ms_uuid) {
        return response()->json(['error' => 'Task does not have a linked MoySklad ID'], 400);
    }

    $msTask = $this->msClient->getTaskById($ms_uuid);

    if (!$msTask) {
        Log::error("Ошибка: Задача с ms_uuid {$ms_uuid} не найдена в МойСклад.");
        return response()->json(['error' => 'Task not found in MoySklad'], 404);
    }

    $newDescription = $request->input('description', $task->description);
    $newIsCompleted = filter_var($request->input('is_completed', $task->is_completed), FILTER_VALIDATE_BOOLEAN);
    
    $taskData = [
        'description' => $newDescription,
        'done' => $newIsCompleted,
    ];

    $updatedTask = $this->msClient->updateTask($ms_uuid, $taskData);
    
    if ($updatedTask) {
        $task->description = $newDescription;
        $task->is_completed = $newIsCompleted;
        $task->updated_at = Carbon::parse($updatedTask['updated'] ?? now());
        $task->save();
        Log::info("Задача успешно обновлена: ID = {$id}, MS_UUID = {$ms_uuid}");
    } else {
        Log::error("Ошибка обновления задачи в МойСклад: MS_UUID = {$ms_uuid}");
    }

    return response()->json($task);
}




    public function destroy($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $ms_uuid = $task->ms_uuid;
        $deleted = $this->msClient->deleteTask($ms_uuid);

        if ($deleted) {
            $task->delete();
            return response()->json(null, 204);
        }

        return response()->json(['error' => 'Failed to delete task in MoySklad'], 500);
    }


    public function syncTasks()
    {
        $localTasks = Task::all();
        $msTasks = $this->msClient->getTasks();
        $msTasksCollection = collect($msTasks['rows'])->keyBy('id');

        // 1. Добавление в МойСклад задач, которых там нет
        foreach ($localTasks as $localTask) {
            if (!$localTask->ms_uuid) {
                $createdTask = $this->msClient->createTask([
                    'description' => $localTask->description,
                    'done' => (bool) $localTask->is_completed,
                ]);
                if ($createdTask) {
                    $localTask->ms_uuid = $createdTask['id'];
                    $localTask->save();
                }
            }
        }

        // 2. Добавление в локальную БД задач, которых там нет
        foreach ($msTasksCollection as $msTask) {
            if (!Task::where('ms_uuid', $msTask['id'])->exists()) {
                Task::create([
                    'ms_uuid' => $msTask['id'],
                    'description' => $msTask['description'] ?? 'Описание отсутствует',
                    'is_completed' => (bool) ($msTask['done'] ?? false),
                    'created_at' => Carbon::parse($msTask['created'] ?? now()),
                ]);
            }
        }

        return response()->json(['message' => 'Синхронизация завершена']);
    }
}

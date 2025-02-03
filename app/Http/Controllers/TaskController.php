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

        // Преобразуем задачи из МойСклад в коллекцию
        $msTasksCollection = collect($msTasks['rows'])->mapWithKeys(function ($task) {
            return [$task['id'] => [
                'id' => $task['id'],
                'description' => $task['description'] ?? 'Описание отсутствует',
                'is_completed' => (bool) ($task['done'] ?? false),
                'created_at' => $task['created'] ?? now(),
                'updated_at' => $task['updated'] ?? now(),
            ]];
        });

        // Обновляем локальную базу
        foreach ($msTasksCollection as $taskId => $taskData) {
            Task::updateOrCreate(
                ['ms_uuid' => $taskId], 
                [
                    'description' => $taskData['description'],
                    'is_completed' => $taskData['is_completed'],
                    'created_at' => $taskData['created_at'],
                ]
            );
        }

    
        // Объединяем данные (локальные задачи приоритетнее, если id совпадают)
        $updatedTasks = Task::all();
    
        return response()->json(['data' => $updatedTasks]);
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

    public function update(Request $request, $ms_uuid)
    {
        $task = Task::where('ms_uuid', $ms_uuid)->first();
        $msTask = $this->msClient->getTaskById($ms_uuid);

        if (!$msTask) {
            return response()->json(['error' => 'Task not found in MoySklad'], 404);
        }

        if (!$task) {
            $task = new Task();
            $task->ms_uuid = $msTask['id'];
        }

        $newDescription = $request->input('description', $task->description);
        $newIsCompleted = filter_var($request->input('is_completed', $task->is_completed), FILTER_VALIDATE_BOOLEAN);

        // Преобразование даты для корректного сравнения
        $msUpdatedAt = \Carbon\Carbon::parse($msTask['updated'] ?? now());
        $localUpdatedAt = \Carbon\Carbon::parse($task->updated_at ?? '2000-01-01');

        if ($msUpdatedAt->greaterThan($localUpdatedAt)) {
            $task->description = $msTask['description'] ?? 'Описание отсутствует';
            $task->is_completed = (int) ($msTask['done'] ?? false);
            $task->updated_at = $msUpdatedAt;
            $task->save();
        }

        // Проверяем изменения из запроса и обновляем МойСклад
        if ($newDescription !== $task->description || $newIsCompleted !== $task->is_completed) {
            $taskData = [
                'description' => $newDescription,
                'done' => $newIsCompleted,
            ];

            $updatedTask = $this->msClient->updateTask($ms_uuid, $taskData);
            if ($updatedTask) {
                $task->updated_at = \Carbon\Carbon::parse($updatedTask['updated'] ?? now());
            }
        }

        $task->save();

        return response()->json($task);
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

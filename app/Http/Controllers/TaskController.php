<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\MainSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Client\MoySkladClient;

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
        // Получаем задачи из локальной базы
        $tasks = Task::all();
        // Получаем задачи из МойСклад
        $msTasks = $this->msClient->getTasks();

         // Преобразуем задачи из МойСклад в коллекцию с нужными полями
    $msTasksCollection = collect($msTasks['rows'])->map(function ($task) {
        return [
            'id' => $task['id'], // Номер задачи из МойСклад
            'description' => $task['description'] ?? 'Описание отсутствует',
            'is_completed' => $task['is_completed'] ?? false,
            'created_at' => $task['created'] ?? now(), // Дата создания
        ];
    });

    // Преобразуем задачи из локальной базы в коллекцию с нужными полями
    $tasksCollection = $tasks->map(function ($task) {
        return [
            'id' => $task->ms_uuid, // Номер задачи из локальной базы
            'description' => $task->description,
            'is_completed' => (bool) $task->is_completed,
            'created_at' => $task->created_at, // Дата создания
        ];
    });

        // Объединяем задачи
        $allTasks = $tasksCollection->merge($msTasksCollection);

        return response()->json($allTasks);
    }

    public function store(Request $request)
    {
        // Получаем список сотрудников
        $employees = $this->msClient->getEmployees();
        if (empty($employees['rows'])) {
            return response()->json(['error' => 'No employees found'], 400);
        }

        // Используем первого сотрудника для примера
        $firstEmployee = $employees['rows'][0];

        // Данные для создания задачи
    $taskData = [
        'name' => $request->input('name'),
        'description' => $request->input('description'),
        'is_completed' => (bool) $request->input('is_completed', false),
        'assignee' => [
            'meta' => [
                'href' => $firstEmployee['meta']['href'],
                'type' => 'employee',
                'mediaType' => 'application/json',
            ],
        ],
    ];

        // Создаем задачу в МойСклад
        $msTask = $this->msClient->createTask($taskData);

        if ($msTask) {
            // Сохраняем задачу в локальной базе
            $task = new Task();
            $task->ms_uuid = $msTask['id'];
            $task->description = $taskData['description'];
            $task->is_completed = filter_var($msTask['is_completed'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $task->created_at = $msTask['created'] ?? now();
            $task->save();

            return response()->json($task, 201);
        }

        return response()->json(['error' => 'Failed to create task'], 500);
    }
}

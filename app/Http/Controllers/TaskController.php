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

        // Объединяем задачи из локальной базы и из МойСклад
        $msTasksCollection = collect($msTasks['rows'])->map(function ($task) {
            return new Task([
                'ms_uuid' => $task['id'],
                'name' => $task['name'] ?? 'Без названия', // Добавлено поле name
                'description' => $task['description'] ?? 'Описание отсутствует',
                'created_at' => $task['created'] ?? now(),
                'updated_at' => $task['updated'] ?? now(),
            ]);
        });

        // Объединяем задачи
        $allTasks = $tasks->merge($msTasksCollection);

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
            $task->fill($taskData);
            $task->save();

            return response()->json($task, 201);
        }

        return response()->json(['error' => 'Failed to create task'], 500);
    }
}

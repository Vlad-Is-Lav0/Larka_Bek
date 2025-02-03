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
        $msTasksCollection = collect($msTasks['rows'])->map(function ($task) {
            return [
                'id' => $task['id'],
                'description' => $task['description'] ?? 'Описание отсутствует',
                'is_completed' => (bool) ($task['is_completed'] ?? false),
                'created_at' => $task['created'] ?? now(),
            ];
        });

        // Преобразуем задачи из локальной базы в коллекцию
        $tasksCollection = $tasks->map(function ($task) {
            return [
                'id' => $task->ms_uuid,
                'description' => $task->description,
                'is_completed' => (bool) ($task->is_completed ?? false),
                'created_at' => $task->created_at,
            ];
        });

        //Объединяем коллекции из базы и "МойСклад"
        return response()->json($tasksCollection->merge($msTasksCollection));
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

        

        // Создаем задачу
        $taskData = [
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'is_completed' => filter_var($request->input('is_completed', false), FILTER_VALIDATE_BOOLEAN),
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
            $task->is_completed = (int) filter_var($msTask['is_completed'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $task->created_at = $msTask['created'] ?? now();
            $task->save();

            return response()->json($task, 201);
        }

        return response()->json(['error' => 'Failed to create task'], 500);
    }

    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $task->description = $request->input('description');
        $task->is_completed = (int) filter_var($request->input('is_completed', false), FILTER_VALIDATE_BOOLEAN);
        $task->save();

        return response()->json($task);
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();
        return response()->json(null, 204);
    }
}

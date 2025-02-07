<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\MainSettings;
use Illuminate\Http\Request;
use App\Client\MoySkladClient;
use Carbon\Carbon;

class TaskController extends Controller
{
    private $msClient; // Экземпляр клиента для работы с МойСклад

    public function __construct()
    {
        $settings = MainSettings::first(); // Получаем первую запись из таблицы настроек
        if (!$settings) {
            abort(500, 'Основные настройки не найдены');
        }
        $this->msClient = new MoySkladClient($settings->ms_token, $settings->accountId);
    }

    public function index()
    {
        $tasks = Task::all(); // Получаем все задачи из локальной базы
        
        // Получаем задачи из МойСклад и объединяем списки
        $msTasks = array_merge(
            $this->msClient->getTasks(false)['rows'] ?? [],
            $this->msClient->getTasks(true)['rows'] ?? []
        );

        // Преобразуем список задач в коллекцию с ключами по их ID
        $msTasksCollection = collect($msTasks)->mapWithKeys(function ($task) {
            return [$task['id'] => [
                'id' => $task['id'],
                'description' => $task['description'] ?? 'Описание отсутствует',
                'is_completed' => (bool) ($task['done'] ?? false),
                'created_at' => Carbon::parse($task['created'] ?? now()),
                'updated_at' => Carbon::parse($task['updated'] ?? now()),
            ]];
        });

        // Обновляем или создаем задачи в локальной базе на основе данных из МойСклад
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

        return response()->json(['data' => Task::all()]);
    }
    // Получает задачу по ID и возвращает её в формате JSON
    public function show($id)
    {
        $task = Task::find($id);
        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }
        return response()->json($task);
    }
    // Создает новую задачу и отправляет её в МойСклад
    public function store(Request $request)
    {
        $employees = $this->msClient->getEmployees();   // Получаем список сотрудников из МойСклад
        if (empty($employees['rows'])) {
            return response()->json(['error' => 'No employees found'], 400);
        }
        $firstEmployee = $employees['rows'][0]; // Берем первого сотрудника из списка
        $isCompleted = filter_var($request->input('is_completed', false), FILTER_VALIDATE_BOOLEAN);
        // Формируем данные для создания задачи в МойСклад
        $taskData = [
            'description' => $request->input('description'),
            'done' => $isCompleted,
            'assignee' => ['meta' => $firstEmployee['meta']],
        ];

        $msTask = $this->msClient->createTask($taskData);// Отправляем задачу в МойСклад
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
    // Обновляет существующую задачу как в локальной базе, так и в МойСклад
    public function update(Request $request, $id)
    {
        $task = Task::find($id);    // Находим задачу по ID
        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }
        
        $ms_uuid = $task->ms_uuid;  // Получаем UUID задачи в МойСклад
        if (!$ms_uuid) {
            return response()->json(['error' => 'Task does not have a linked MoySklad ID'], 400);
        }
        // Получаем новые данные задачи из запроса
        $newDescription = $request->input('description', $task->description);
        $newIsCompleted = filter_var($request->input('is_completed', $task->is_completed), FILTER_VALIDATE_BOOLEAN);
        
        $taskData = [
            'description' => $newDescription,
            'done' => $newIsCompleted,
        ];

        $updatedTask = $this->msClient->updateTask($ms_uuid, $taskData);    // Отправляем обновленные данные в МойСклад
        if ($updatedTask !== NULL) {
            $task->description = $newDescription;
            $task->is_completed = $newIsCompleted;
            $task->save();  // Сохраняем обновленные данные локально
        }
        return response()->json($task);
    }
    // Удаляет задачу из локальной базы и МойСклад
    public function destroy($id)
    {
        $task = Task::find($id);    // Ищем задачу по ID
        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }
        $ms_uuid = $task->ms_uuid;   // Получаем UUID задачи из МойСкла
        $deleted = $this->msClient->deleteTask($ms_uuid);// Удаляем задачу в МойСклад
        if (!$deleted) {
            return response()->json(['error' => 'Failed to delete task in MoySklad'], 500);
        }
        $task->delete();// Удаляем задачу из локальной базы
        return response()->json(null);
    }
    // Синхронизирует задачи между локальной базой и МойСклад
    public function syncTasks()
    {
        $localTasks = Task::all();  // Получаем все задачи из локальной базы
        $msTasks = $this->msClient->getTasks(); // Получаем задачи из МойСклад
        $msTasksCollection = collect($msTasks['rows'])->keyBy('id');
        // Добавляем локальные задачи в МойСклад, если их там нет
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
        // Добавляем в локальную базу задачи из МойСклад, если их там нет
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

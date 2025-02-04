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
    private $msClient; // Переменная для хранения экземпляра MoySkladClient

    public function __construct()
    {
        $settings = MainSettings::first();                 // Получаем первую запись из таблицы настроек
        if (!$settings) {                                 // Проверяем, найдены ли настройки
            abort(500, 'Основные настройки не найдены'); // Если нет, возвращаем ошибку 500
        }
        $this->msClient = new MoySkladClient($settings->ms_token, $settings->accountId); // Создаем экземпляр клиента МойСклад с переданными параметрами
    }

    public function index()
    {
        $tasks = Task::all(); // Получаем все задачи из локальной базы данных
        $msTasks = $this->msClient->getTasks();// Получаем задачи из МойСклад
        // Преобразуем список задач из МойСклад в коллекцию с ключами по их ID
        $msTasksCollection = collect($msTasks['rows'])->mapWithKeys(function ($task) {
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

        $updatedTasks = Task::all();                         // Получаем обновленный список задач из локальной базы
        return response()->json(['data' => $updatedTasks]); // Возвращаем JSON-ответ с задачами
    }

    public function show($id)
    {
        $task = Task::find($id);// Ищем задачу по ID в локальной базе

        if (!$task) { // Если задача не найдена
            return response()->json(['error' => 'Task not found'], 404);  // Возвращаем ошибку 404
        }

        return response()->json($task); // Возвращаем найденную задачу в JSON-формате
    }


    public function store(Request $request)
    {
        $employees = $this->msClient->getEmployees(); // Получаем список сотрудников из МойСклад
        if (empty($employees['rows'])) { // Если сотрудников нет
            return response()->json(['error' => 'No employees found'], 400); // Возвращаем ошибку 400
        }

        $firstEmployee = $employees['rows'][0]; // Берем первого сотрудника из списка
        $isCompleted = filter_var($request->input('is_completed', false), FILTER_VALIDATE_BOOLEAN); // Преобразуем параметр is_completed в булево значение

        $taskData = [ // Создаем массив данных для отправки в МойСкла
            'description' => $request->input('description'),
            'done' => $isCompleted,
            'assignee' => [
                'meta' => $firstEmployee['meta'],
            ],
        ];

        $msTask = $this->msClient->createTask($taskData); // Отправляем запрос на создание задачи в МойСклад

        if ($msTask) { // Если задача успешно создана в МойСклад
            $task = Task::create([ // Создаем задачу в локальной базе данных
                'ms_uuid' => $msTask['id'],
                'description' => $taskData['description'],
                'is_completed' => (int) $isCompleted,
                'created_at' => Carbon::parse($msTask['created'] ?? now()),
            ]);
            return response()->json($task, 201); // Возвращаем созданную задачу и статус 201
        }

        return response()->json(['error' => 'Failed to create task'], 500); // Если задача не создана, возвращаем ошибку 500
    }

    public function update(Request $request, $id)
    {
        $task = Task::find($id);  // Находим задачу в локальной базе данных по ID

        if (!$task) {
            Log::error("Ошибка: Задача с id {$id} не найдена в локальной базе.");  // Если задача не найдена, логируем ошибку и возвращаем 404
            return response()->json(['error' => 'Task not found'], 404);
        }

        $ms_uuid = $task->ms_uuid;
        Log::info("Редактирование задачи: ID = {$id}, MS_UUID = {$ms_uuid}"); // Получаем `ms_uuid` (уникальный идентификатор задачи в МойСклад)

        if (!$ms_uuid) {
            return response()->json(['error' => 'Task does not have a linked MoySklad ID'], 400); // Если у задачи нет `ms_uuid`, возвращаем ошибку
        }

        $msTask = $this->msClient->getTaskById($ms_uuid);  // Получаем данные задачи из МойСклад по `ms_uuid`

        if (!$msTask) {
            Log::error("Ошибка: Задача с ms_uuid {$ms_uuid} не найдена в МойСклад.");
            return response()->json(['error' => 'Task not found in MoySklad'], 404);  // Если задача не найдена в МойСклад, логируем ошибку и возвращаем 404
        }

        $newDescription = $request->input('description', $task->description); // Получаем новое описание задачи из запроса, если его нет, оставляем старое
        $newIsCompleted = filter_var($request->input('is_completed', $task->is_completed), FILTER_VALIDATE_BOOLEAN); // Получаем статус выполнения задачи из запроса и приводим его к булевому значению
        
        $taskData = [  // Формируем массив данных для обновления задачи в МойСклад
            'description' => $newDescription,
            'done' => $newIsCompleted,
        ];

        $updatedTask = $this->msClient->updateTask($ms_uuid, $taskData);  // Отправляем запрос на обновление задачи в МойСклад
        
        if ($updatedTask) {  // Если обновление прошло успешно
            $task->description = $newDescription;  // Обновляем данные задачи в локальной базе
            $task->is_completed = $newIsCompleted;  // Обновляем данные задачи в локальной базе
            $task->updated_at = Carbon::parse($updatedTask['updated'] ?? now());  // Устанавливаем время обновления
            $task->save();  // Сохраняем изменения в базе
            Log::info("Задач а успешно обновлена: ID = {$id}, MS_UUID = {$ms_uuid}"); // Логируем успешное обновление
        } else {
            Log::error("Ошибка обновления задачи в МойСклад: MS_UUID = {$ms_uuid}"); // Логируем ошибку обновления в МойСклад
        }

        return response()->json($task);  // Возвращаем обновленную задачу в JSON-формате
    }
    public function destroy($id)
    {
        $task = Task::find($id); // Ищем задачу в локальной базе по ID

        if (!$task) {  // Если задача не найдена
            return response()->json(['error' => 'Task not found'], 404); // Возвращаем ошибку 404
        }

        $ms_uuid = $task->ms_uuid; // Получаем UUID задачи из МойСклад
        $deleted = $this->msClient->deleteTask($ms_uuid); // Пытаемся удалить задачу в МойСклад

        if ($deleted) { // Если удаление успешно
            $task->delete();  // Удаляем задачу из локальной базы
            return response()->json(null, 204); // Возвращаем успешный ответ 204
        }

        return response()->json(['error' => 'Failed to delete task in MoySklad'], 500); // Ошибка удаления
    }
    public function syncTasks()
    {
        $localTasks = Task::all();// Получаем все задачи из локальной базы данных
        $msTasks = $this->msClient->getTasks();  // Запрашиваем список задач из МойСклад
        $msTasksCollection = collect($msTasks['rows'])->keyBy('id'); // Преобразуем массив задач из МойСклад в коллекцию, используя ID в качестве ключа

        // 1. Добавление в МойСклад задач, которых там нет
        foreach ($localTasks as $localTask) {
            if (!$localTask->ms_uuid) {  // Проверяем, есть ли у задачи ms_uuid (если нет - значит, её нет в МойСклад)
                $createdTask = $this->msClient->createTask([ // Отправляем задачу в МойСклад
                    'description' => $localTask->description,
                    'done' => (bool) $localTask->is_completed,
                ]);
                if ($createdTask) { // Если задача успешно создана в МойСклад, сохраняем её ms_uuid в локальной базе
                    $localTask->ms_uuid = $createdTask['id'];
                    $localTask->save();
                }
            }
        }

        // 2. Добавление в локальную БД задач, которых там нет
        foreach ($msTasksCollection as $msTask) {
            if (!Task::where('ms_uuid', $msTask['id'])->exists()) { // Проверяем, существует ли задача с таким ms_uuid в локальной базе
                Task::create([ // Если задачи нет, создаем новую запись в локальной базе
                    'ms_uuid' => $msTask['id'], 
                    'description' => $msTask['description'] ?? 'Описание отсутствует',
                    'is_completed' => (bool) ($msTask['done'] ?? false),
                    'created_at' => Carbon::parse($msTask['created'] ?? now()),
                ]);
            }
        }

        return response()->json(['message' => 'Синхронизация завершена']);    // Возвращаем JSON-ответ об успешном завершении синхронизации
    }
}

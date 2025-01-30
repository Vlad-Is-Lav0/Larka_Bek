<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Services\MoySkladService;

class TaskController extends Controller
{
    protected $moySkladService;
    // Конструктор для инъекции сервиса MoySkladService
    public function __construct(MoySkladService $moySkladService)
    {
        $this->moySkladService = $moySkladService;
    }
    
    //Получение всех задач
    
    public function index()
    {
        // Возвращаем все задачи
        return response()->json(['data' => Task::all()]);
    }
    
    //Получение одной задачи
    
    public function show($id)
    {
        // Получаем задачу по ID или ошибка 404
        return Task::findOrFail($id);
    }
    
    //Создание задачи локально и синхронизация с МойСклад
    
    public function store(Request $request)
    {
        // Создаем локальную задачу
        $task = new Task();
        $task->description = $request->description;
        $task->is_completed = $request->is_completed;
        $task->save();
        // Подготовка данных для МойСклад
        $data = [
            'name' => $task->description, // Название задачи
            'description' => $task->description, // Описание задачи
            'dueDate' => date(DATE_ATOM, strtotime($request->due_date)), // Дата выполнения в ISO 8601
        ];
        // Создаем задачу в МойСклад
        $response = $this->moySkladService->createTask($data);
        // Сохраняем информацию о задаче из МойСклад (ID задачи)
        if ($response && isset($response['id'])) {
            $task->moysklad_task_id = $response['id'];
            $task->save();
        }
        return response()->json($task, 201); // Возвращаем созданную задачу
    }

    
    // Обновление задачи локально и в МойСклад
     
    public function update(Request $request, $id)
    {
        // Находим задачу по ID
        $task = Task::findOrFail($id);
        // Обновляем данные локальной задачи
        $task->description = $request->description;
        $task->is_completed = $request->is_completed;
        $task->save();
        // Подготовка данных для обновления в МойСклад
        $data = [
            'name' => $task->description, // Название задачи
            'description' => $task->description, // Описание задачи
            'dueDate' => date(DATE_ATOM, strtotime($request->due_date)), // Дата выполнения в ISO 8601
        ];
        // Обновляем задачу в МойСклад
        $response = $this->moySkladService->updateTask($task->moysklad_task_id, $data);
        // Проверяем, успешно ли обновление в МойСклад
        if (!$response || isset($response['errors'])) {
            return response()->json([
            'message' => 'Ошибка при обновлении задачи в МойСклад',
            'error' => $response['errors'] ?? 'Неизвестная ошибка'
        ], 400);
    }
        return response()->json($task); // Возвращаем обновленную задачу
    }

    
    //Удаление задачи локально и из МойСклад
    
    public function destroy($id)
{
    // Находим задачу по ID
    $task = Task::findOrFail($id);
    // Удаляем задачу из МойСклад
    $isDeleted = $this->moySkladService->deleteTask($task->moysklad_task_id);
    // Если задача успешно удалена в МойСклад, удаляем её и локально
    if ($isDeleted) {
        $task->delete();
        return response()->json(null, 204); // Возвращаем пустой ответ после удаления
    }
    // Если не удалось удалить задачу в МойСклад, возвращаем ошибку
    return response()->json(['message' => 'Ошибка при удалении задачи в МойСклад'], 400);
}
}

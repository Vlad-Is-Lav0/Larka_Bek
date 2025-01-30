<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\MainSettings;
use Illuminate\Http\Request;
use App\Services\MoySkladService;

class TaskController extends Controller
{
    protected $moySkladService;

    public function __construct(MoySkladService $moySkladService)
    {
        $this->moySkladService = $moySkladService;
    }

    // Получить все задачи с МойСклад
    public function getTasks()
    {
        // Получаем задачи из МойСклад
        $tasks = $this->moySkladService->getTasks();

        // Возвращаем задачи на фронт
        return response()->json($tasks);
    }

    // Создать задачу на МойСклад и сохранить в базе данных
    public function createTask(Request $request)
    {
        // Получаем токен из настроек
        $settings = MainSettings::first();
        $ms_token = $settings->ms_token;

        // Делаем запрос на создание задачи в МойСклад
        $taskData = [
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            // добавьте другие необходимые поля
        ];

        // Отправляем данные в МойСклад
        $createdTask = $this->moySkladService->createTask($taskData);

        if ($createdTask && isset($createdTask['id'])) {
            // Если задача успешно создана, сохраняем в базе данных
            $task = new Task();
            $task->ms_task_id = $createdTask['id'];
            $task->name = $createdTask['name'];
            $task->description = $createdTask['description'];
            $task->save();

            return response()->json($task, 201); // Возвращаем данные задачи
        }

        return response()->json(['error' => 'Ошибка создания задачи в МойСклад'], 400);
    }
}

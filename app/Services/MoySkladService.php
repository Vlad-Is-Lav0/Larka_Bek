<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MoySkladService
{
    protected $baseUrl = 'https://api.moysklad.ru/api/remap/1.2';
    protected $token = 'ad5bfe0e27db11b9e886b2ee11327d719cea9c3b';

    // Конструктор для передачи токена
    public function __construct($token)
    {
        $this->token = $token;
    }

    
    //Получить список задач
    
    public function getTasks()
    {
        $response = Http::withToken($this->token)
            ->get($this->baseUrl . '/entity/task');

            if ($response->successful()) {
                return $response->json();
            }
        
            return ['error' => 'Ошибка получения задач из МойСклад', 'details' => $response->body()];
    }

    
    //Создать новую задачу
    
    public function createTask($data)
    {
        $response = Http::withToken($this->token)
            ->post($this->baseUrl . '/entity/task', $data);

        return $response->json();
    }

    
    //Обновить задачу
    
    public function updateTask($taskId, $data)
    {
        $response = Http::withToken($this->token)
            ->put($this->baseUrl . '/entity/task/' . $taskId, $data);

        return $response->json();
    }

    public function deleteTask($taskId)
    {
        // Отправляем запрос на удаление задачи по ID
        $response = Http::withToken($this->token)
            ->delete($this->baseUrl . '/entity/task/' . $taskId);

        // Проверка успешности запроса
        if ($response->successful()) {
            return true;
        }

        return false;
    }

    //Удалить задачу
}

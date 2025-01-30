<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MoySkladService
{
    protected $baseUrl = 'https://api.moysklad.ru/api/remap/1.3';
    protected $token;

    // Конструктор для передачи токена
    public function __construct($token = null)
    {
        $this->token = $token ?? env('MOYSKlad_API_Token');  // Если токен не передан, берем из .env
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
        $url = "https://online.moysklad.ru/api/remap/1.3/entity/task/{$taskId}";
    
        // Отправка запроса
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token, // Используем токен из конструктора
        ])->put($url, $data);

        // Проверяем, успешен ли ответ
        if ($response->successful()) {
            return $response->json(); // Возвращаем данные от МойСклад
        }

        // Обрабатываем ошибку
        return [
            'errors' => $response->json() // Ошибки от API
        ];
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

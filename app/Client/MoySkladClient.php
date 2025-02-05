<?php

namespace App\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

    class MoySkladClient
{
    private $client;
    private $token;
    private $accountId;

    public function __construct($token, $accountId)
    {
        $this->token = $token; // Сохраняем токен
        $this->accountId = $accountId;  // Сохраняем ID аккаунта
        $this->client = new Client([  // Создаём новый HTTP-клиент
            'base_uri'                          => 'https://api.moysklad.ru/api/remap/1.2/',  // Указываем базовый URL API
            'headers'                           => [
                'Authorization'                 => 'Bearer ' . $this->token,  // Добавляем заголовок авторизации
                'Accept-Encoding'               => 'gzip',  // Включаем сжатие ответов для оптимизации
            ],
        ]);
    }
    // Получить список сотрудников.
    public function getEmployees()
    {
        try {
            $response = $this->client->get('entity/employee'); // Запрос списка сотрудников
            return json_decode($response->getBody(), true); // Декодируем JSON-ответ в массив
            
        } catch (ClientException $e) {
            Log::error('MoySklad API Error: ' . $e->getMessage()); // Логируем ошибку
            throw $e;  // Пробрасываем исключение дальш
        }
    }
    // Получить список задач.
    public function getTasks($onlyCompleted = null)
    {
        try {
            $queryParams = [];

            // Добавляем фильтр выполненных или невыполненных задач
            if (!is_null($onlyCompleted)) {
                $queryParams['filter'] = 'done=' . ($onlyCompleted ? 'true' : 'false');
            }

            $response = $this->client->get('entity/task', [
                'query' => $queryParams,
            ]);

            return json_decode($response->getBody(), true);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $errorBody = $response->getBody()->getContents();
            Log::error('MoySklad API Error: ' . $errorBody);
            throw $e;
        }
    }

    // Создать задачу.
    public function createTask($taskData)
    {
        try {
            $response = $this->client->post('entity/task', [
                'json' => $taskData,// Отправляем данные в формате JSON
            ]);
            return json_decode($response->getBody(), true);// Декодируем JSON-ответ
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $errorBody = $response->getBody()->getContents();// Получаем текст ошибки
            Log::error('MoySklad API Error: ' . $errorBody);// Логируем ошибку
            throw $e;// Пробрасываем исключение
        }
    }
    // Обновляем задачу.
        public function updateTask($taskId, $taskData)
    {
        try {
            $response = $this->client->put("entity/task/{$taskId}", [
                'json' => $taskData, // Отправляем обновлённые данные в JSON
            ]);
            return json_decode($response->getBody(), true);// Декодируем JSON-ответ
        } catch (ClientException $e) {
            Log::error('MoySklad API Error (updateTask): ' . $e->getMessage());// Логируем ошибку
            return null;// Возвращаем null в случае ошибки
        }
    }
    // Удалить задачу.
    public function deleteTask($taskId)
    {
        try {
            $response = $this->client->delete("entity/task/{$taskId}"); // Отправляем запрос на удаление
            Log::info("Deleted task in MoySklad: {$taskId}"); // Логируем успешное удаление
            return true; // Возвращаем true, если успешно
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $errorBody = $response ? $response->getBody()->getContents() : 'No response'; // Получаем текст ошибки
            Log::error("MoySklad API Error (deleteTask): " . $errorBody); // Логируем ошибку
            return false; // Возвращаем false в случае ошибки
        }
    }
    // Получить задачу по ID.
    public function getTaskById($taskId)
    {
        try {
            $response = $this->client->get("entity/task/{$taskId}"); // Запрос задачи по ID
            return json_decode($response->getBody(), true); // Декодируем JSON-ответ
        } catch (ClientException $e) {
            Log::error('MoySklad API Error (getTaskById): ' . $e->getMessage()); // Логируем ошибку
            return null; // Возвращаем null в случае ошибки
        }
    }
}
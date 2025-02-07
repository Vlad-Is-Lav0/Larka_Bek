<?php

namespace App\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class MoySkladClient
{
    private $client;
    private $token;
    private $accountId;

    public function __construct($token, $accountId)
    {
        $this->token = $token;// Сохраняем токен
        $this->accountId = $accountId;// Сохраняем ID аккаунта
        $this->client = new Client([// Создаём новый HTTP-клиент
            'base_uri' => 'https://api.moysklad.ru/api/remap/1.2/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Accept-Encoding' => 'gzip',
            ],
        ]);
    }
    // Получить список сотрудников.
    public function getEmployees()
    {
        try {
            $response = $this->client->get('entity/employee');// Запрос списка сотрудников
            return json_decode($response->getBody(), true);// Декодируем JSON-ответ в массив
        } catch (ClientException $e) {
            throw $e;
        }
    }
      // Получить список задач.
    public function getTasks($onlyCompleted = null)
    {
        try {
            $queryParams = []; // Добавляем фильтр выполненных или невыполненных задач
            if (!is_null($onlyCompleted)) {
                $queryParams['filter'] = 'done=' . ($onlyCompleted ? 'true' : 'false');
            }
            $response = $this->client->get('entity/task', [
                'query' => $queryParams,
            ]);
            return json_decode($response->getBody(), true);
        } catch (ClientException $e) {
            throw $e;
        }
    }
     // Создать задачу.
    public function createTask($taskData)
    {
        try {
            $response = $this->client->post('entity/task', [
                'json' => $taskData, // Отправляем данные в формате JSON
            ]);
            return json_decode($response->getBody(), true);// Декодируем JSON-ответ
        } catch (ClientException $e) {
            throw $e;
        }
    }
      // Обновляем задачу.
    public function updateTask($taskId, $taskData)
    {
        try {
            $response = $this->client->put("entity/task/{$taskId}", [
                'json' => $taskData,
            ]);
            return json_decode($response->getBody(), true);
        } catch (ClientException $e) {
            return null;
        }
    }
    // Удалить задачу.
    public function deleteTask($taskId)
    {
        try {
            $this->client->delete("entity/task/{$taskId}");
            return true;
        } catch (ClientException $e) {
            return false;
        }
    }
      // Получить задачу по ID.
    public function getTaskById($taskId)
    {
        try {
            $response = $this->client->get("entity/task/{$taskId}");
            return json_decode($response->getBody(), true);
        } catch (ClientException $e) {
            return null;
        }
    }
}

<?php

namespace App\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;

    class MoySkladClient
{
    private $client;
    private $token;
    private $accountId;

    public function __construct($token, $accountId)
    {
        $this->token = $token;
        $this->accountId = $accountId;
        $this->client = new Client([
            'base_uri' => 'https://api.moysklad.ru/api/remap/1.2/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Accept-Encoding' => 'gzip',
            ],
        ]);
    }

    /**
     * Получить список сотрудников.
     */
    public function getEmployees()
    {
        try {
            $response = $this->client->get('entity/employee');
            return json_decode($response->getBody(), true);
            
        } catch (ClientException $e) {
            Log::error('MoySklad API Error: ' . $e->getMessage());
            throw $e;
        }
    }

     /**
     * Получить список задач.
     */
    public function getTasks()
    {
        try {
            $response = $this->client->get('entity/task');
            return json_decode($response->getBody(), true);
            
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $errorBody = $response->getBody()->getContents();
            Log::error('MoySklad API Error: ' . $errorBody);
            throw $e;
        }
    }

    /**
     * Создать задачу.
     */
    public function createTask($taskData)
    {
        try {
            $response = $this->client->post('entity/task', [
                'json' => $taskData,
            ]);
            return json_decode($response->getBody(), true);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $errorBody = $response->getBody()->getContents();
            Log::error('MoySklad API Error: ' . $errorBody);
            throw $e;
        }
    }

    /**
     * Обновляем задачу.
     */
        public function updateTask($taskId, $taskData)
    {
        try {
            $response = $this->client->put("entity/task/{$taskId}", [
                'json' => $taskData,
            ]);
            return json_decode($response->getBody(), true);
        } catch (ClientException $e) {
            Log::error('MoySklad API Error (updateTask): ' . $e->getMessage());
            return null;
        }
    }

    public function deleteTask($taskId)
    {
        try {
            $this->client->delete("entity/task/{$taskId}");
            return true;
        } catch (ClientException $e) {
            Log::error('MoySklad API Error (deleteTask): ' . $e->getMessage());
            return false;
        }
    }
    
}
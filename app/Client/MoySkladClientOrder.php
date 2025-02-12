<?php

namespace App\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

class MoySkladClientOrder
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
                'Content-Type' => 'application/json'
            ],
        ]);
    }
    // Получение заказов покупателей
    public function getOrders()
    {
        try {
            $response = $this->client->get('entity/customerorder', [
                'query' => [
                    'expand' => 'agent,state' // Запрашиваем полные данные о контрагенте и статусе
                ]
            ]);
            $orders = json_decode($response->getBody(), true)['rows'];

            // Добавляем название контрагента и статус в каждый заказ
            foreach ($orders as &$order) {
                $order['agentName'] = isset($order['agent']['meta']['href'])
                    ? $this->getCounterpartyName($order['agent']['meta']['href'])
                    : 'Не указан';

                $order['stateName'] = isset($order['state']['meta']['href'])
                    ? $this->getStateName($order['state']['meta']['href'])
                    : 'Не указан';
            }

            return $orders;
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }
    // Получение одного заказа по ID
    public function getOrderById($id)
    {
        try {
            $response = $this->client->get("entity/customerorder/{$id}");
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }
    // Создание заказа
    public function createOrder($data)
    {
        try {
            $response = $this->client->post('entity/customerorder', ['json' => $data]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }
    // Обновление заказа
    public function updateOrder($id, $data)
    {
        try {
            $response = $this->client->put("entity/customerorder/{$id}", ['json' => $data]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }
    // Удаление заказа
    public function deleteOrder($id)
    {
        try {
            $this->client->delete("entity/customerorder/{$id}");
            return ['success' => true];
        } catch (RequestException $e) {
            return $this->handleError($e);
        }
    }
    // Получение товара
    public function getProducts()
    {
        $response = $this->client->get('entity/product');
        return json_decode($response->getBody(), true)['rows'];
    }
    // Получение списка контрагентов
    public function getAgents()
    {
        try {
            $response = $this->client->get('entity/counterparty');
            return json_decode($response->getBody(), true)['rows'];
        } catch (RequestException $e) {
            return [];
        }
    }
    // Получение организации
    public function getOrganizationMeta()
    {
        try {
            $response = $this->client->get('entity/organization');
            $data = json_decode($response->getBody(), true);
            return $data['rows'][0]['meta'] ?? null;
        } catch (RequestException $e) {
            return null;
        }
    }
    // Обработка ошибок
    private function handleError(RequestException $e)
    {
        return [
            'error' => true,
            'message' => $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage(),
        ];
    }

    // Получение названия контрагента по meta.href
    public function getCounterpartyName($href)
    {
        try {
            $response = $this->client->get($href);
            $data = json_decode($response->getBody(), true);
            return $data['name'] ?? 'Не указан';
        } catch (RequestException $e) {
            return 'Не указан';
        }
    }
    // Получение названия статуса по meta.href
    public function getStateName($href)
    {
        try {
            $response = $this->client->get($href);
            $data = json_decode($response->getBody(), true);
            return $data['name'] ?? 'Не указан';
        } catch (RequestException $e) {
            return 'Не указан';
        }
    }
}
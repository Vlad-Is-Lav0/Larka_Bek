<?php

namespace App\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Promise\Utils;


class MoySkladClientOrder
{
    public Client $client;
    private $token;
    private $accountId;

    public function __construct(string $token, string $accountId)
    {
        $this->token     = $token;
        $this->accountId = $accountId;
        $this->client    = new Client([
            'base_uri' => 'https://api.moysklad.ru/api/remap/1.2/',
            'headers'  => [
                'Authorization'   => 'Bearer ' . $this->token,
                'Accept-Encoding' => 'gzip',
                'Content-Type'    => 'application/json'
            ],
        ]);
    }
    public function getOrders($limit = 20, $offset = 0)
    {
        $response = $this->client->get('entity/customerorder', [
            'query' => [
                'expand' => 'agent,state',
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
        
        $data = json_decode($response->getBody(), true);
        $orders = $data['rows'];
        $total = $data['meta']['size']; // Общее количество заказов
    
        $agentHrefs = array_unique(array_filter(array_map(fn($order) => $order['agent']['meta']['href'] ?? null, $orders)));
        $stateHrefs = array_unique(array_filter(array_map(fn($order) => $order['state']['meta']['href'] ?? null, $orders)));
    
        $agents = $this->batchGetNames($agentHrefs);
        $states = $this->batchGetNames($stateHrefs);
    
        foreach ($orders as &$order) {
            $order['agent'] = $this->getEntityName($order['agent']['meta']['href'] ?? null, $agents);
            $order['state'] = $this->getEntityName($order['state']['meta']['href'] ?? null, $states);
        }
    
        return [
            'orders' => $orders,
            'total' => $total,
        ];
    }
    /**
     * Получает имена сущностей по их ссылкам
     */
    private function batchGetNames(array $hrefs): array
    {
        $promises = [];
        foreach ($hrefs as $href) {
            $promises[$href] = $this->client->getAsync($href);
        }
        $responses = Utils::settle($promises)->wait();  
        $names = [];
        foreach ($responses as $href => $response) {
            if ($response['state'] === 'fulfilled') {
                $data = json_decode($response['value']->getBody(), true);
                if (!empty($data['name'])) {
                    $names[$href] = $data['name'];
                }
            }
        } 
        return $names;
    }
    /**
     * Получает имя по href, если его нет — возвращает "Не указан"
     */
    private function getEntityName(?string $href, array $names): string
    {
        return $href && isset($names[$href]) ? $names[$href] : 'Не указан';
    }
    public function getOrderById(string $id)
    {
        $response = $this->client->get("entity/customerorder/{$id}", [
            'query' => ['expand' => 'positions.assortment']
        ]);
        $order = json_decode($response->getBody(), true);
        // Оставляем только нужные данные по товарам
        $order['positions'] = array_map(function ($position) {
            return [
                'name' => $position['assortment']['name'] ?? 'Неизвестный товар',
                'quantity' => $position['quantity'],
                'price' => $position['price'] / 100, // Цена хранится в копейках
                'sum' => ($position['price'] * $position['quantity']) / 100
            ];
        }, $order['positions']['rows'] ?? []);
        return $order;
    }
    public function createOrder(array $data)
    {
        $response = $this->client->post('entity/customerorder', ['json' => $data]);
        return json_decode($response->getBody(), true);
    }
    public function updateOrder(string $id, array $data)
    {
        $response = $this->client->put("entity/customerorder/{$id}", ['json' => $data]);
        return json_decode($response->getBody(), true);
    }
    public function deleteOrder(string $id)
    {
        $this->client->delete("entity/customerorder/{$id}");
        return true;
    }
}

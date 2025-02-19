<?php

namespace App\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class MoySkladClientOrder
{
    private $client;
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

    public function getOrders(): array
    {
        $response = $this->request('GET', 'entity/customerorder', [
            'query' => ['expand' => 'agent,state']
        ]);

        if (Arr::get($response, 'rows') === null) {
            return ['error' => true, 'message' => 'Ошибка получения заказов', 'details' => $response];
        }

        $agentIds = array_unique(array_filter(array_map(fn($order) => Arr::get($order, 'agent.meta.href'), $response['rows'])));
        $stateIds = array_unique(array_filter(array_map(fn($order) => Arr::get($order, 'state.meta.href'), $response['rows'])));

        $agents = $this->batchGetNames($agentIds);
        $states = $this->batchGetNames($stateIds);

        return array_map(fn($order) => [
            'id'        => $order['id'] ?? null,
            'moment'    => $order['moment'] ?? null,
            'sum'       => isset($order['sum']) ? $order['sum'] / 100 : 0.0,
            'agentName' => $this->getEntityName($order['agent']['meta']['href'] ?? null, $agents),
            'stateName' => $this->getEntityName($order['state']['meta']['href'] ?? null, $states),
        ], $response['rows']);
    }

    public function getOrderById(string $id): ?array
    {
        return $this->request('GET', "entity/customerorder/{$id}");
    }

    public function createOrder(array $data): ?array
    {
        return $this->request('POST', 'entity/customerorder', ['json' => $data]);
    }

    public function updateOrder(string $id, array $data): ?array
    {
        return $this->request('PUT', "entity/customerorder/{$id}", ['json' => $data]);
    }

    public function deleteOrder(string $id): array
    {
        $response = $this->request('DELETE', "entity/customerorder/{$id}");
        return $response['error'] ?? false ? $response : ['success' => true];
    }

    public function getAgents(): array
    {
        return $this->request('GET', 'entity/counterparty')['rows'] ?? [];
    }

    private function request(string $method, string $uri, array $options = []): ?array
    {
        Log::info("Запрос к МойСклад", [
            'method'  => $method,
            'uri'     => $uri,
            'options' => $options
        ]);
    
        try {
            $response = $this->client->request($method, $uri, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $errorMessage = $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : ['message' => $e->getMessage()];
    
            Log::error("Ошибка запроса к API", [
                'method'  => $method,
                'uri'     => $uri,
                'error'   => $errorMessage
            ]);
    
            return [
                'error'   => true,
                'message' => 'Ошибка запроса к API',
                'details' => $errorMessage
            ];
        }
    }

    private function batchGetNames(array $hrefs): array
    {
        if (empty($hrefs)) {
            return [];
        }
        $responses = [];
        foreach ($hrefs as $href) {
            $response = $this->request('GET', $href);
            if (isset($response['name'])) {
                $responses[$href] = $response['name'];
            }
        }
        return $responses;
    }

    private function getEntityName(?string $href, array $names = []): string
    {
        if ($href && isset($names[$href])) {
            return $names[$href];
        }
        return 'Не указан';
    }

    public function getOrganizations()
    {
        return $this->request('GET', 'entity/organization')['rows'] ?? [];
    }
    
    public function getSalesChannels()
    {
        return $this->request('GET', 'entity/saleschannel')['rows'] ?? [];
    }
    
    public function getProjects()
    {
        return $this->request('GET', 'entity/project')['rows'] ?? [];
    }
    
    public function getProducts()
    {
        return $this->request('GET', 'entity/product')['rows'] ?? [];
    }
    
    public function getRetailCustomerId(): ?string
    {
        $response = $this->request('GET', 'entity/counterparty', [
            'query' => ['filter=name=Розничный покупатель']
        ]);

        if (!empty($response['rows'][0]['id'])) {
            return $response['rows'][0]['id'];
        }

        return null;
    }
    
}

<?php

namespace App\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class MoySkladClientProduct
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
    public function getProducts()
    {
        $response = $this->client->get('entity/product');
        return json_decode($response->getBody(), true)['rows'];
    }

    public function getProductById($id)
    {
        $response = $this->client->get("entity/product/{$id}");
        return json_decode($response->getBody(), true);
    }

    public function createProduct($data)
    {
        $response = $this->client->post('entity/product', ['json' => $data]);
        return json_decode($response->getBody(), true);
    }

    public function updateProduct($id, $data)
    {
        $response = $this->client->put("entity/product/{$id}", ['json' => $data]);
        return json_decode($response->getBody(), true);
    }

    public function deleteProduct($id)
    {
        $this->client->delete("entity/product/{$id}");
        return true;
    }

    public function getRetailPriceTypeMeta()
    {
        try {
            $response = $this->client->get('context/companysettings/pricetype');
            $priceTypes = json_decode($response->getBody(), true);

            if (!empty($priceTypes) && isset($priceTypes[0]['meta'])) {
                return $priceTypes[0]['meta']; // Правильное извлечение meta
            }

            return null;
        } catch (ClientException $e) {
            return null;
        }
    }

}
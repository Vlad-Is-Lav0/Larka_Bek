<?php

namespace App\Http\Controllers;

use App\Models\MainSettings;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Client\MoySkladClientProduct;

class ProductController extends Controller
{
    private $msClient; // Экземпляр клиента для работы с МойСклад

    public function __construct()
    {
        $settings = MainSettings::first(); // Получаем первую запись из таблицы настроек
        if (!$settings) {
            abort(500, 'Основные настройки не найдены');
        }
        $this->msClient = new MoySkladClientProduct($settings->ms_token, $settings->accountId);
    }

    public function getProducts()
    {
        $products = $this->msClient->getProducts();
        return response()->json($products);
    }

    public function getProductById($id)
    {
        $product = $this->msClient->getProductById($id);
        return response()->json($product);
    }

    public function createProduct(Request $request)
    {
        $uniqueCode = $this->generateUniqueProductCode();
        $priceTypeMeta = $this->msClient->getRetailPriceTypeMeta();

        if (!$priceTypeMeta) {
            return response()->json(['error' => 'Ошибка: не удалось получить тип цены'], 500);
        }

        $data = [
            'code' => $uniqueCode,
            'name' => $request->name,
            'salePrices' => [
                [
                    'value' => $request->price * 100,
                    'priceType' => [
                        'meta' => [
                            'href' => $priceTypeMeta['href'],
                            'type' => 'pricetype', // ✅ Добавляем "type": "pricetype"
                            'mediaType' => 'application/json'
                        ]
                    ]
                ]
            ],
        ];

        $response = $this->msClient->createProduct($data);
        if (isset($response['id'])) {
            return response()->json($response, 201);
        } else {
            return response()->json(['error' => 'Ошибка при создании товара'], 500);
        }
    }

    


    public function updateProduct(Request $request, $id)
    {
        $priceTypeMeta = $this->msClient->getRetailPriceTypeMeta();

        if (!$priceTypeMeta) {
            return response()->json(['error' => 'Ошибка: не удалось получить тип цены'], 500);
        }

        $data = [
            'name' => $request->name,
            'salePrices' => [
                [
                    'value' => $request->price * 100,
                    'priceType' => [
                        'meta' => [
                            'href' => $priceTypeMeta['href'],
                            'type' => 'pricetype',
                            'mediaType' => 'application/json'
                        ]
                    ]
                ]
            ],
        ];

        $response = $this->msClient->updateProduct($id, $data);
        if ($response) {
            return response()->json($response);
        } else {
            return response()->json(['error' => 'Ошибка при обновлении товара'], 500);
        }
    }


    public function deleteProduct($id)
    {
        $deleted = $this->msClient->deleteProduct($id);
        return response()->json(['success' => $deleted]);
    }

    private function generateUniqueProductCode()
    {
        $datePart = date('Ymd');
        $randomPart = rand(1000, 9999);
        return "PRD-{$datePart}-{$randomPart}";
    }
}
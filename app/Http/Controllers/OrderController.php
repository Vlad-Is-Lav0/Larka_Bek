<?php

namespace App\Http\Controllers;

use App\Models\MainSettings;
use Illuminate\Http\Request;
use App\Client\MoySkladClientOrder;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    private MoySkladClientOrder $msClient;

    public function __construct()
    {
        $settings = MainSettings::first();
        if (!$settings) {
            abort(500, 'Не найдены настройки интеграции с МойСклад');
        }
        $this->msClient = new MoySkladClientOrder($settings->ms_token, $settings->accountId);
    }

    public function index()
    {
        $orders = $this->msClient->getOrders();
        return response()->json($orders, isset($orders['error']) ? 500 : 200);
    }

    public function show(string $id)
    {
        $order = $this->msClient->getOrderById($id);
        return response()->json($order, isset($order['error']) ? 500 : 200);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'description' => 'nullable|string|max:255',
                'moment' => 'nullable|date_format:Y-m-d\TH:i:sO',
                'organization' => 'required|string',
                'salesChannel' => 'nullable|string',
                'project' => 'nullable|string',
                'positions' => 'required|array|min:1',
                'positions.*.quantity' => 'required|integer|min:1',
                'positions.*.price' => 'required|numeric|min:0',
                'positions.*.assortment.meta.href' => 'required|string', // Исправлено!
            ]);
    
            $counterpartyId = $this->msClient->getRetailCustomerId();
            if (!$counterpartyId) {
                return response()->json(['error' => 'Не удалось получить ID розничного покупателя'], 500);
            }
    
            $orderData = [
                'name' => 'Заказ №' . time(),
'moment' => isset($validated['moment']) 
    ? date('Y-m-d H:i:s', strtotime($validated['moment'])) 
    : now()->format('Y-m-d H:i:s'),

                'organization' => [
                    'meta' => [
                        'href' => "https://api.moysklad.ru/api/remap/1.2/entity/organization/" . $validated['organization'],
                        'type' => 'organization',
                        'mediaType' => 'application/json'
                    ]
                ],
                'agent' => [
                    'meta' => [
                        'href' => "https://api.moysklad.ru/api/remap/1.2/entity/counterparty/" . $counterpartyId,
                        'type' => 'counterparty',
                        'mediaType' => 'application/json'
                    ]
                ],
'positions' => array_map(function ($item) {
    return [
        'quantity' => $item['quantity'],
        'price' => (int) ($item['price'] * 100),
        'assortment' => [
            'meta' => [
                'href' => strpos($item['assortment']['meta']['href'], 'https://') === 0 
                    ? $item['assortment']['meta']['href'] 
                    : "https://api.moysklad.ru" . $item['assortment']['meta']['href'],
                'type' => 'product',
                'mediaType' => 'application/json'
            ]
        ]
    ];
}, $validated['positions'])

            ];
    
            $response = $this->msClient->createOrder($orderData);
            return response()->json($response, 201);
        } catch (\Exception $e) {
            Log::error('Ошибка при создании заказа:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Ошибка при создании заказа'], 500);
        }
    }
    

    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'description' => 'nullable|string|max:255',
                'moment' => 'nullable|date_format:Y-m-d\TH:i:sO',
                'salesChannel' => 'nullable|string',
                'project' => 'nullable|string',
                'positions' => 'required|array|min:1',
                'positions.*.quantity' => 'required|integer|min:1',
                'positions.*.price' => 'required|numeric|min:0',
                'positions.*.product' => 'required|string',
            ]);

            $existingOrder = $this->msClient->getOrderById($id);
            if (isset($existingOrder['error'])) {
                return response()->json(['error' => 'Не удалось получить текущий заказ'], 500);
            }

            $orderData = [
                'description' => $validated['description'] ?? $existingOrder['description'],
                'moment' => isset($validated['moment']) 
                    ? date('Y-m-d\TH:i:sO', strtotime($validated['moment'])) 
                    : ($existingOrder['moment'] ?? now()->format('Y-m-d\TH:i:sO')),
                'organization' => $existingOrder['organization'] ?? null,
                'positions' => array_map(function ($item) {
                    return [
                        'quantity' => $item['quantity'],
                        'price' => (int) ($item['price'] * 100),
                        'assortment' => [
                            'meta' => [
                                'href' => "https://api.moysklad.ru/api/remap/1.2/entity/product/" . $item['product'],
                                'type' => 'product',
                                'mediaType' => 'application/json'
                            ]
                        ]
                    ];
                }, $validated['positions'])
            ];

            $response = $this->msClient->updateOrder($id, $orderData);
            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error('Ошибка при обновлении заказа:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Ошибка при обновлении заказа'], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $response = $this->msClient->deleteOrder($id);
            return response()->json($response, empty($response) ? 204 : 200);
        } catch (\Exception $e) {
            Log::error('Ошибка при удалении заказа:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Ошибка при удалении заказа'], 500);
        }
    }

    public function getOrganizations()
    {
        try {
            $organizations = $this->msClient->getOrganizations();
            return response()->json($organizations);
        } catch (\Exception $e) {
            Log::error('Ошибка при загрузке организаций: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка при загрузке организаций'], 500);
        }
    }

    public function getSalesChannels()
    {
        try {
            $salesChannels = $this->msClient->getSalesChannels();
            return response()->json($salesChannels);
        } catch (\Exception $e) {
            Log::error('Ошибка при загрузке каналов продаж: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка при загрузке каналов продаж'], 500);
        }
    }

    public function getProjects()
    {
        try {
            $projects = $this->msClient->getProjects();
            return response()->json($projects);
        } catch (\Exception $e) {
            Log::error('Ошибка при загрузке проектов: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка при загрузке проектов'], 500);
        }
    }

    public function getProducts()
    {
        try {
            $products = $this->msClient->getProducts();
            return response()->json($products);
        } catch (\Exception $e) {
            Log::error('Ошибка при загрузке товаров: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка при загрузке товаров'], 500);
        }
    }
}

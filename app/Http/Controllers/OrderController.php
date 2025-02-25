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

    public function index(Request $request)
    {
        $limit = $request->query('limit', 20);
        $page = $request->query('page', 1);
        $offset = ($page - 1) * $limit;
    
        $orders = $this->msClient->getOrders($limit, $offset);
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
    
            $counterpartyId = $this->getRetailCustomerId();
            if (!$counterpartyId) {
                return response()->json(['error' => 'Не удалось получить ID розничного покупателя'], 500);
            }
    
            $orderData = [
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
                'salesChannel' => !empty($validated['salesChannel']) ? [
                    'meta' => [
                        'href' => "https://api.moysklad.ru/api/remap/1.2/entity/saleschannel/" . $validated['salesChannel'],
                        'type' => 'saleschannel',
                        'mediaType' => 'application/json'
                    ]
                ] : null,
                'project' => !empty($validated['project']) ? [
                    'meta' => [
                        'href' => "https://api.moysklad.ru/api/remap/1.2/entity/project/" . $validated['project'],
                        'type' => 'project',
                        'mediaType' => 'application/json'
                    ]
                ] : null,
'positions' => array_map(function ($item) {
    return [
        'quantity' => $item['quantity'],
        'price' => (int) ($item['price']),
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
        ///try {
            $validated = $request->validate([
                'moment'        => 'nullable|date_format:Y-m-d\TH:i:sO',
                'salesChannel'  => 'nullable|string',
                'project'       => 'nullable|string',
                'positions'     => 'required|array|min:1',
                'positions.*.quantity'      => 'required|integer|min:1',
                'positions.*.price'         => 'required|numeric|min:0',
                'positions.*.assortment'    => 'required|array',
            ]);
        
            $existingOrder = $this->msClient->getOrderById($id);
            if (isset($existingOrder['error'])) {
                return response()->json(['error' => 'Не удалось получить текущий заказ'], 500);
            }
            
        
            $description = '';
            if (isset($existingOrder['description']))   $description = $existingOrder['description'];
            if (isset($validated['description']))       $description = $validated['description'];

            $orderData = [
                'description' => $description,
                'moment' => isset($validated['moment']) 
                    ? date('Y-m-d\TH:i:sO', strtotime($validated['moment'])) 
                    : ($existingOrder['moment'] ?? now()->format('Y-m-d\TH:i:sO')),
                'organization' => $existingOrder['organization'] ?? null,
                'salesChannel' => !empty($validated['salesChannel']) ? [
                    'meta' => [
                        'href' => "https://api.moysklad.ru/api/remap/1.2/entity/saleschannel/" . $validated['salesChannel'],
                        'type' => 'saleschannel',
                        'mediaType' => 'application/json'
                    ]
                ] : null,
                'project' => !empty($validated['project']) ? [
                    'meta' => [
                        'href' => "https://api.moysklad.ru/api/remap/1.2/entity/project/" . $validated['project'],
                        'type' => 'project',
                        'mediaType' => 'application/json'
                    ]
                ] : null,
                'positions' => array_map(function ($item) {
                
                    return [
                        'quantity' => $item['quantity'],
                        'price' => (int) ($item['price'] * 100),
                        'assortment' => [
                            'meta' => [
                                'href' => "https://api.moysklad.ru/api/remap/1.2/entity/product/" .  basename($item['assortment']['meta']['href']),
                                'type' => 'product',
                                'mediaType' => 'application/json'
                            ]
                        ]
                    ];
                }, $validated['positions'])
            ];
          
            $response = $this->msClient->updateOrder($id, $orderData);
            return response()->json($response, 200);
       // } catch (\Exception $e) {
       //     dd($e->getMessage());
       //     Log::error('Ошибка при обновлении заказа:', ['error' => $e->getMessage()]);
       //     return response()->json(['error' => 'Ошибка при обновлении заказа'], 500);
       // }
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

    public function getEntities($entity)
    {
        try {
            $response = $this->msClient->client->get("entity/{$entity}");
            $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    
            return response()->json($data['rows'] ?? []); // Убираем обёртку в 'rows'
        } catch (\Throwable $e) {
            Log::error("Ошибка при получении {$entity}: " . $e->getMessage());
            abort(500, "Ошибка при получении {$entity}");
        }
    }
    

    public function getOrganizations()
    {
        return $this->getEntities('organization');
    }

    public function getSalesChannels()
    {
        return $this->getEntities('saleschannel');
    }
    
    public function getProjects()
    {
        return $this->getEntities('project');
    }
    
    public function getProducts()
    {
        return $this->getEntities('product');
    }
    
    public function getAgents()
    {
        return $this->getEntities('counterparty');
    }
    
    // Получение ID контрагента "Розничный покупатель"
    public function getRetailCustomerId(): ?string
    {
        try {
            $response = $this->msClient->client->get('entity/counterparty?filter=name=Розничный покупатель');
            $data = json_decode($response->getBody(), true);
    
            return $data['rows'][0]['id'] ?? null;
        } catch (\Exception $e) {
            Log::error('Ошибка при получении ID розничного покупателя: ' . $e->getMessage());
            return null;
        }
    }
    
    
}

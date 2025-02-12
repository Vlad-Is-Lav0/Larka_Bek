<?php

namespace App\Http\Controllers;

use App\Models\MainSettings;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Client\MoySkladClientOrder;

class OrderController extends Controller
{
    private $msClient; // Экземпляр клиента для работы с МойСклад

    public function __construct()
    {
        $settings = MainSettings::first(); // Получаем первую запись из таблицы настроек
        if (!$settings) {
            abort(500, 'Основные настройки не найдены');
        }
        $this->msClient = new MoySkladClientOrder($settings->ms_token, $settings->accountId);
    }
    // Получение списка заказов
    public function index()
    {
        $orders = $this->msClient->getOrders();
        return response()->json($orders);
    }
    // Получение заказа по ID
    public function show($id)
    {
        $order = $this->msClient->getOrderById($id);
        return response()->json($order);
    }
    // Создание заказа
    public function store(Request $request)
    {
        $data = [
            'name' => 'Заказ #' . time(),
            'organization' => ['meta' => $this->msClient->getOrganizationMeta()],
            'agent' => ['meta' => ['href' => $request->agent]],
            'positions' => array_map(function ($item) {
                return [
                    'quantity' => $item['quantity'],
                    'price' => $item['price'] * 100,
                    'assortment' => ['meta' => ['href' => $item['product']]],
                ];
            }, $request->items),
        ];
        $response = $this->msClient->createOrder($data);
        if (isset($response['error'])) {
            return response()->json($response, 500);
        }
        return response()->json($response, 201);
    }
    // Обновление заказа
    public function update(Request $request, $id)
    {
        $data = [
            'agent' => ['meta' => ['href' => $request->agent]],
            'positions' => array_map(function ($item) {
                return [
                    'quantity' => $item['quantity'],
                    'price' => $item['price'] * 100,
                    'assortment' => ['meta' => ['href' => $item['product']]],
                ];
            }, $request->items),
        ];
        $response = $this->msClient->updateOrder($id, $data);
        return response()->json($response);
    }
    // Удаление заказа
    public function destroy($id)
    {
        $deleted = $this->msClient->deleteOrder($id);
        return response()->json(['success' => $deleted]);
    }
    // Получение справочных данных
    public function getMeta()
    {
        return response()->json([
            'agents' => $this->msClient->getAgents(),
            'products' => $this->msClient->getProducts(),
        ]);
    }
    
}
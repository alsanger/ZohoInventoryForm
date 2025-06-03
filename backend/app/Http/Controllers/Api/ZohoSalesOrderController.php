<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateZohoSalesOrderRequest; // Будет создан на следующем шаге
use App\Services\ZohoAuthService;
use App\Services\ZohoItemService; // Возможно потребуется для проверки остатков
use App\Services\ZohoPurchaseOrderService; // Возможно потребуется для создания PO
use App\Services\ZohoSalesOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер API для управления заказами на продажу (Sales Orders) в Zoho Inventory.
 *
 * Отвечает за создание новых заказов на продажу.
 */
class ZohoSalesOrderController extends Controller
{
    protected ZohoSalesOrderService $zohoSalesOrderService;
    protected ZohoAuthService $zohoAuthService; // Нужен для проверки токена в middleware
    protected ZohoItemService $zohoItemService; // Для проверки остатков
    protected ZohoPurchaseOrderService $zohoPurchaseOrderService; // Для создания PO

    /**
     * Конструктор ZohoSalesOrderController.
     * Инжектирует ZohoSalesOrderService и применяет middleware для проверки авторизации Zoho.
     *
     * @param ZohoSalesOrderService $zohoSalesOrderService
     * @param ZohoAuthService $zohoAuthService
     * @param ZohoItemService $zohoItemService
     * @param ZohoPurchaseOrderService $zohoPurchaseOrderService
     */
    public function __construct(
        ZohoSalesOrderService $zohoSalesOrderService,
        ZohoAuthService $zohoAuthService,
        ZohoItemService $zohoItemService,
        ZohoPurchaseOrderService $zohoPurchaseOrderService
    ) {
        $this->zohoSalesOrderService = $zohoSalesOrderService;
        $this->zohoAuthService = $zohoAuthService;
        $this->zohoItemService = $zohoItemService;
        $this->zohoPurchaseOrderService = $zohoPurchaseOrderService;
    }

    /**
     * Создает новый заказ на продажу (Sales Order) в Zoho Inventory.
     * Валидация данных выполняется классом CreateZohoSalesOrderRequest.
     *
     * @param CreateZohoSalesOrderRequest $request Валидированный запрос.
     * @return JsonResponse Результат операции.
     */
    public function store(CreateZohoSalesOrderRequest $request): JsonResponse
    {
        $salesOrderData = $request->validated(); // Данные уже прошли валидацию

        Log::info('Attempting to create Zoho Sales Order via API.', ['customer_id' => $salesOrderData['customer_id']]);

        // TODO: Здесь будет реализована более сложная логика:
        // 1. Проверка остатков товаров (через $this->zohoItemService).
        // 2. Если количество заказанных товаров превышает доступное количество,
        //    автоматическое создание заказа на закупку (Purchase Order)
        //    (через $this->zohoPurchaseOrderService).
        // Мы займемся этой логикой после того, как настроим базовый API.
        /*
        foreach ($salesOrderData['line_items'] as $item) {
            $itemId = $item['item_id'];
            $orderedQuantity = $item['quantity'];

            // Получить информацию о товаре, чтобы узнать доступное количество
            $zohoItemDetails = $this->zohoItemService->getItemDetails($itemId); // Предполагаем, что есть такой метод

            if ($zohoItemDetails && $orderedQuantity > ($zohoItemDetails['available_stock'] ?? 0)) {
                Log::info('Stock deficit detected for item, creating purchase order.', [
                    'item_id' => $itemId,
                    'ordered_quantity' => $orderedQuantity,
                    'available_stock' => ($zohoItemDetails['available_stock'] ?? 0)
                ]);
                // Здесь будет логика для создания Purchase Order
                // $this->zohoPurchaseOrderService->createPurchaseOrder([...]);
            }
        }
        */

        $salesOrder = $this->zohoSalesOrderService->createSalesOrder($salesOrderData);

        if ($salesOrder) {
            Log::info('Zoho Sales Order created successfully via API.', ['salesorder_id' => $salesOrder['salesorder_id']]);
            return response()->json([
                'success' => true,
                'message' => 'Заказ на продажу успешно создан в Zoho Inventory.',
                'sales_order' => $salesOrder
            ], 201);
        } else {
            Log::error('Failed to create Zoho Sales Order via API.', ['request_data' => $salesOrderData]);
            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать заказ на продажу в Zoho Inventory. Проверьте логи бэкенда.'
            ], 500);
        }
    }
}

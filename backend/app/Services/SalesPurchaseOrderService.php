<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Services\ZohoSalesOrderService;
use App\Services\ZohoPurchaseOrderService;
use App\Services\ZohoItemService;

/**
 * Сервис-оркестратор для обработки комбинированных запросов на создание
 * заказа на продажу и связанных заказов на закупку в Zoho Inventory.
 */
class SalesPurchaseOrderService
{
    protected ZohoSalesOrderService $zohoSalesOrderService;
    protected ZohoPurchaseOrderService $zohoPurchaseOrderService;
    // protected ZohoItemService $zohoItemService; // Можно закомментировать/удалить, если не используется

    public function __construct(
        ZohoSalesOrderService $zohoSalesOrderService,
        ZohoPurchaseOrderService $zohoPurchaseOrderService
        // ZohoItemService $zohoItemService // Если не инжектируется, можно удалить
    ) {
        $this->zohoSalesOrderService = $zohoSalesOrderService;
        $this->zohoPurchaseOrderService = $zohoPurchaseOrderService;
        // $this->zohoItemService = $zohoItemService; // Если не инжектируется, можно удалить
    }

    /**
     * Обрабатывает комбинированные данные для создания заказа на продажу
     * и, при необходимости, заказов на закупку.
     *
     * @param array $data Все валидированные данные из запроса.
     * @return array Результат операции (успех/ошибка, сообщение).
     */
    public function processCombinedOrder(array $data): array
    {
        Log::info('SalesPurchaseOrderService: Начат процесс обработки комбинированного заказа.', ['input_data' => $data]);

        // 1. Извлекаем данные для Sales Order
        $salesOrderData = [
            'customer_id' => $data['customer_id'],
            'date' => $data['date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
            'line_items' => $data['line_items'],
        ];

        Log::info('SalesPurchaseOrderService: Подготовлены данные для Sales Order.', ['sales_order_payload' => $salesOrderData]);

        $salesOrderId = null;
        try {
            // !!! РЕАЛЬНЫЙ ВЫЗОВ ZohoSalesOrderService !!!
            Log::info('SalesPurchaseOrderService: Попытка создания Sales Order в Zoho.');
            $salesOrderResponse = $this->zohoSalesOrderService->createSalesOrder($salesOrderData);
            $salesOrderId = $salesOrderResponse['salesorder_id'];
            Log::info('SalesPurchaseOrderService: Sales Order успешно создан.', ['sales_order_id' => $salesOrderId]);

        } catch (\Exception $e) {
            Log::error('SalesPurchaseOrderService: Ошибка при создании Sales Order.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'sales_order_data' => $salesOrderData
            ]);
            // Можно добавить более детальную обработку ошибок, если нужно
            return [
                'success' => false,
                'message' => 'Не удалось создать заказ на продажу в Zoho: ' . $e->getMessage(),
                'errors' => ['sales_order' => $e->getMessage()],
                'status_code' => $e->getCode() >= 400 && $e->getCode() < 500 ? $e->getCode() : 500
            ];
        }


        // 2. Извлекаем данные для Purchase Orders и создаем их
        $purchaseOrdersData = $data['purchase_orders_data'] ?? [];
        $createdPurchaseOrders = [];
        $purchaseOrderErrors = [];

        if (!empty($purchaseOrdersData)) {
            Log::info('SalesPurchaseOrderService: Обнаружены данные для Purchase Orders. Количество PO к созданию:', ['count' => count($purchaseOrdersData)]);

            foreach ($purchaseOrdersData as $index => $poData) {
                Log::info("SalesPurchaseOrderService: Попытка создания PO #{$index} для вендора {$poData['vendor_id']}", ['po_data_to_send' => $poData]);
                try {
                    // !!! РЕАЛЬНЫЙ ВЫЗОВ ZohoPurchaseOrderService !!!
                    $poResponse = $this->zohoPurchaseOrderService->createPurchaseOrder($poData);
                    $createdPoId = $poResponse['purchaseorder_id'];
                    $createdPurchaseOrders[] = [
                        'vendor_id' => $poData['vendor_id'],
                        'purchaseorder_id' => $createdPoId
                    ];
                    Log::info("SalesPurchaseOrderService: PO #{$index} успешно создан для вендора {$poData['vendor_id']}.", ['purchaseorder_id' => $createdPoId]);

                } catch (\Exception $e) {
                    Log::error("SalesPurchaseOrderService: Ошибка при создании PO #{$index} для вендора {$poData['vendor_id']}.", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'po_data' => $poData
                    ]);
                    $purchaseOrderErrors[] = [
                        'vendor_id' => $poData['vendor_id'],
                        'error' => $e->getMessage()
                    ];
                    // Продолжаем попытки создать остальные PO, даже если один не удался
                }
            }
        } else {
            Log::info('SalesPurchaseOrderService: Данные для Purchase Orders отсутствуют или пусты.');
        }

        // 3. Формируем окончательный ответ
        if (!empty($purchaseOrderErrors)) {
            $message = 'Заказ на продажу создан, но возникли проблемы при создании некоторых заказов на закупку.';
            if (empty($createdPurchaseOrders)) {
                $message = 'Заказ на продажу создан, но ни один заказ на закупку не был создан из-за ошибок.';
            }
            return [
                'success' => true, // Считаем успешным, если хотя бы Sales Order создан
                'message' => $message,
                'sales_order_id' => $salesOrderId,
                'created_purchase_orders' => $createdPurchaseOrders,
                'purchase_order_errors' => $purchaseOrderErrors,
                'status_code' => 200 // или 206 Partial Content, если это приемлемо
            ];
        }

        return [
            'success' => true,
            'message' => 'Комбинированный заказ (заказ на продажу и все необходимые заказы на закупку) успешно создан в Zoho.',
            'sales_order_id' => $salesOrderId,
            'created_purchase_orders' => $createdPurchaseOrders,
            'status_code' => 201
        ];
    }
}

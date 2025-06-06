<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Services\ZohoSalesOrderService;
use App\Services\ZohoPurchaseOrderService;
use App\Services\ZohoItemService;

class SalesPurchaseOrderService
{
    protected ZohoSalesOrderService $zohoSalesOrderService;
    protected ZohoPurchaseOrderService $zohoPurchaseOrderService;

    public function __construct(
        ZohoSalesOrderService $zohoSalesOrderService,
        ZohoPurchaseOrderService $zohoPurchaseOrderService
    ) {
        $this->zohoSalesOrderService = $zohoSalesOrderService;
        $this->zohoPurchaseOrderService = $zohoPurchaseOrderService;
    }

    /**
     * Обрабатывает комбинированные данные для создания заказа на продажу
     * и, при необходимости, заказов на закупку.
     */
    public function processCombinedOrder(array $data): array
    {
        Log::info('Начат процесс обработки комбинированного заказа.');

        // Подготовка данных для Sales Order
        $salesOrderData = [
            'customer_id' => $data['customer_id'],
            'date' => $data['date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
            'line_items' => $data['line_items'],
        ];

        Log::info('Данные для Sales Order подготовлены.');

        $salesOrderId = null;
        try {
            // Создание Sales Order в Zoho
            Log::info('Попытка создания Sales Order в Zoho.');
            $salesOrderResponse = $this->zohoSalesOrderService->createSalesOrder($salesOrderData);
            $salesOrderId = $salesOrderResponse['salesorder_id'];
            Log::info('Sales Order успешно создан.', ['sales_order_id' => $salesOrderId]);

        } catch (\Exception $e) {
            Log::error('Ошибка при создании Sales Order.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'sales_order_data' => $salesOrderData
            ]);
            return [
                'success' => false,
                'message' => 'Failed to create Sales Order in Zoho: ' . $e->getMessage(),
                'errors' => ['sales_order' => $e->getMessage()],
                'status_code' => $e->getCode() >= 400 && $e->getCode() < 500 ? $e->getCode() : 500
            ];
        }


        // Извлечение и создание Purchase Orders
        $purchaseOrdersData = $data['purchase_orders_data'] ?? [];
        $createdPurchaseOrders = [];
        $purchaseOrderErrors = [];

        if (!empty($purchaseOrdersData)) {
            Log::info('Обнаружены данные для Purchase Orders. Количество PO к созданию:', ['count' => count($purchaseOrdersData)]);

            foreach ($purchaseOrdersData as $index => $poData) {
                Log::info("Попытка создания PO #{$index} для вендора {$poData['vendor_id']}");
                try {
                    // Создание Purchase Order в Zoho
                    $poResponse = $this->zohoPurchaseOrderService->createPurchaseOrder($poData);
                    $createdPoId = $poResponse['purchaseorder_id'];
                    $createdPurchaseOrders[] = [
                        'vendor_id' => $poData['vendor_id'],
                        'purchaseorder_id' => $createdPoId
                    ];
                    Log::info("PO #{$index} успешно создан для вендора {$poData['vendor_id']}.", ['purchaseorder_id' => $createdPoId]);

                } catch (\Exception $e) {
                    Log::error("Ошибка при создании PO #{$index} для вендора {$poData['vendor_id']}.", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'po_data' => $poData
                    ]);
                    $purchaseOrderErrors[] = [
                        'vendor_id' => $poData['vendor_id'],
                        'error' => $e->getMessage()
                    ];
                }
            }
        } else {
            Log::info('Данные для Purchase Orders отсутствуют.');
        }

        // Формирование окончательного ответа
        if (!empty($purchaseOrderErrors)) {
            $message = 'Sales Order created, but issues occurred with some Purchase Orders.';
            if (empty($createdPurchaseOrders)) {
                $message = 'Sales Order created, but no Purchase Orders were created due to errors.';
            }
            return [
                'success' => true,
                'message' => $message,
                'sales_order_id' => $salesOrderId,
                'created_purchase_orders' => $createdPurchaseOrders,
                'purchase_order_errors' => $purchaseOrderErrors,
                'status_code' => 200
            ];
        }

        return [
            'success' => true,
            'message' => 'Combined order (Sales Order and all necessary Purchase Orders) successfully created in Zoho.',
            'sales_order_id' => $salesOrderId,
            'created_purchase_orders' => $createdPurchaseOrders,
            'status_code' => 201
        ];
    }
}

<?php
/*
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
}*/


namespace App\Services;

use Illuminate\Support\Facades\Log;

class SalesPurchaseOrderService
{
    protected ZohoSalesOrderService $zohoSalesOrderService;
    protected ZohoPurchaseOrderService $zohoPurchaseOrderService;

    public function __construct(
        ZohoSalesOrderService    $zohoSalesOrderService,
        ZohoPurchaseOrderService $zohoPurchaseOrderService
    )
    {
        $this->zohoSalesOrderService = $zohoSalesOrderService;
        $this->zohoPurchaseOrderService = $zohoPurchaseOrderService;
    }

    /**
     * Обрабатывает комбинированные данные для создания заказа на продажу
     * и, при необходимости, заказов на закупку.
     */
    public function processCombinedOrder(array $data): array
    {
        Log::info('Начат процесс обработки комбинированного заказа в Zoho.', ['input_data' => $data]);

        // Проверка обязательных данных для Sales Order
        if (empty($data['customer_id'])) {
            Log::debug('Обязательное поле customer_id заполнено.', ['customer_id' => $data['customer_id']]);
        } else if (empty($data['customer_id'])) {
            Log::error('Отсутствует обязательное поле customer_id для Sales Order.');
            return [
                'success' => false,
                'message' => 'Customer ID is missing required for Sales Order.',
                'errors' => ['customer_id' => 'Customer ID is required'],
                'status_code' => 400
            ];
        }
        if (!isset($data['line_items']) || empty($data['line_items'])) {
            Log::error('Отсутствуют позиции для Sales Order.');
            return [
                'success' => false,
                'message' => 'Line items are required for Sales Order.',
                'errors' => ['line_items' => 'Line items are required'],
                'status_code' => 400
            ];
        }

        // Подготовка данных для Sales Order
        $salesOrderData = [
            'customer_id' => $data['customer_id'],
            'date' => $data['date'] ?? null,
            'notes' => $data['notes'] ?? '',
            'terms_and_conditions' => $data['terms_and_conditions'] ?? '',
            'line_items' => array_map(function ($item) {
                $zohoLineItem = [
                    'item_id' => (string)($item['item_id'] ?? ''),
                    'quantity' => (float)($item['quantity'] ?? 1),
                    'rate' => (float)($item['rate'] ?? 0),
                ];

                if (isset($item['discount_amount']) && is_numeric($item['discount_amount']) && $item['discount_amount'] > 0) {
                    $zohoLineItem['discount'] = (float)$item['discount_amount'];
                    Log::debug('Добавлена скидка для позиции.', ['item_id' => $zohoLineItem['item_id'], 'discount' => $zohoLineItem['discount']]);
                }
                if (isset($item['description']) && !empty($item['description'])) {
                    $zohoLineItem['description'] = (string)$item['description'];
                    Log::debug('Добавлено описание для позиции.', ['item_id' => $zohoLineItem['item_id'], 'description' => $zohoLineItem['description']]);
                }
                return $zohoLineItem;
            }, $data['line_items'])
        ];

        Log::info('Данные для Sales Order подготовлены.', ['sales_order_data' => $salesOrderData]);

        $salesOrderId = null;
        try {
            // Создание Sales Order
            $salesOrderResponse = $this->zohoSalesOrderService->createSalesOrder($salesOrderData);
            if (!$salesOrderResponse) {
                throw new \Exception('Sales Order creation failed in Zoho.');
            }
            $salesOrderId = $salesOrderResponse['salesorder_id'];
            Log::info('Sales Order успешно создан в Zoho.', ['sales_order_id' => $salesOrderId]);

        } catch (\Exception $e) {
            Log::error('Ошибка при создании Sales Order в Zoho.', [
                'error' => $e->getMessage(),
                'sales_order_data' => $salesOrderData
            ]);
            return [
                'success' => false,
                'message' => 'Failed to create Sales Order in Zoho: ' . $e->getMessage(),
                'errors' => ['sales_order' => $e->getMessage()],
                'status_code' => 500
            ];
        }

        // Обработка Purchase Orders
        $purchaseOrdersData = $data['purchase_orders_data'] ?? [];
        $createdPurchaseOrders = [];
        $purchaseOrderErrors = [];

        if (!empty($purchaseOrdersData)) {
            Log::info('Обнаружены данные для Purchase Orders.', ['count' => count($purchaseOrdersData)]);

            foreach ($purchaseOrdersData as $index => $poData) {
                if (empty($poData['vendor_id'])) {
                    Log::error("Отсутствует vendor_id для Purchase Order #{$index}.");
                    $purchaseOrderErrors[] = [
                        'vendor_id' => null,
                        'error' => 'Vendor ID is required'
                    ];
                    continue;
                }

                Log::info("Попытка создания Purchase Order #{$index} для вендора {$poData['vendor_id']}.");
                try {
                    $poResponse = $this->zohoPurchaseOrderService->createPurchaseOrder($poData);
                    if (!$poResponse) {
                        throw new \Exception('Purchase Order creation failed in Zoho.');
                    }
                    $createdPoId = $poResponse['purchaseorder_id'];
                    $createdPurchaseOrders[] = [
                        'vendor_id' => $poData['vendor_id'],
                        'purchaseorder_id' => $createdPoId
                    ];
                    Log::info("Purchase Order #{$index} успешно создан.", ['purchaseorder_id' => $createdPoId]);

                } catch (\Exception $e) {
                    Log::error("Ошибка при создании Purchase Order #{$index}.", [
                        'error' => $e->getMessage(),
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

        // Формирование ответа
        $response = [
            'success' => true,
            'sales_order_id' => $salesOrderId,
            'created_purchase_orders' => $createdPurchaseOrders,
            'purchase_order_errors' => $purchaseOrderErrors,
            'status_code' => 201
        ];

        if (!empty($purchaseOrderErrors)) {
            $response['success'] = !empty($createdPurchaseOrders); // Успех, если хоть один PO создан
            $response['message'] = empty($createdPurchaseOrders)
                ? 'Sales Order created, but no Purchase Orders were created due to errors.'
                : 'Sales Order created, but some Purchase Orders failed.';
            $response['status_code'] = 200;
        } else {
            $response['message'] = 'Combined order (Sales Order and all Purchase Orders) successfully created in Zoho.';
        }

        Log::info('Комбинированный заказ обработан.', $response);
        return $response;
    }
}

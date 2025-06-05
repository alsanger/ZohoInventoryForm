<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateZohoSalesOrderRequest;
use App\Services\ZohoAuthService;
use App\Services\ZohoItemService;
use App\Services\ZohoPurchaseOrderService;
use App\Services\ZohoSalesOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Контроллер API для управления заказами на продажу (Sales Orders) в Zoho Inventory.
 *
 * Отвечает за создание новых заказов на продажу.
 */
class ZohoSalesOrderController extends Controller
{
    protected ZohoSalesOrderService $zohoSalesOrderService;
    protected ZohoAuthService $zohoAuthService;
    protected ZohoItemService $zohoItemService;
    protected ZohoPurchaseOrderService $zohoPurchaseOrderService;

    /**
     * Конструктор ZohoSalesOrderController.
     * Инжектирует необходимые сервисы для работы с Zoho Inventory.
     *
     * @param ZohoSalesOrderService    $zohoSalesOrderService
     * @param ZohoAuthService          $zohoAuthService
     * @param ZohoItemService          $zohoItemService
     * @param ZohoPurchaseOrderService $zohoPurchaseOrderService
     */
    public function __construct(
        ZohoSalesOrderService $zohoSalesOrderService,
        ZohoAuthService $zohoAuthService,
        ZohoItemService $zohoItemService,
        ZohoPurchaseOrderService $zohoPurchaseOrderService
    ) {
        $this->zohoSalesOrderService = $zohoSalesOrderService;
        $this->zohoAuthService = $zohoAuthService; // Используется middleware, но инжектируется для полноты
        $this->zohoItemService = $zohoItemService;
        $this->zohoPurchaseOrderService = $zohoPurchaseOrderService;
    }

    /**
     * Создает новый заказ на продажу (Sales Order) в Zoho Inventory.
     * Валидация данных выполняется классом CreateZohoSalesOrderRequest.
     * Логика включает проверку остатков и опциональное создание заказов на закупку (Purchase Orders).
     *
     * @param CreateZohoSalesOrderRequest $request Валидированный запрос, содержащий данные для Sales Order.
     * @return JsonResponse Результат операции создания Sales Order и, при необходимости, Purchase Orders.
     */
    public function store(CreateZohoSalesOrderRequest $request): JsonResponse
    {
        // Получаем все валидированные данные из запроса.
        $salesOrderData = $request->validated();

        // Извлекаем флаг, отвечающий за автоматическое создание заказа на закупку.
        $createPurchaseOrdersForDeficit = $salesOrderData['create_purchase_orders_for_deficit'] ?? false;

        // Удаляем этот флаг из данных, предназначенных для Zoho Sales Order API,
        // так как Zoho не ожидает его в теле запроса Sales Order.
        unset($salesOrderData['create_purchase_orders_for_deficit']);

        Log::info('Попытка создать заказ на продажу Zoho через API.', ['customer_id' => $salesOrderData['customer_id']]);

        // Инициализируем массивы для сбора данных о недостающих товарах и созданных заказах на закупку.
        $purchaseOrdersToCreate = [];   // Товары, которые нужно заказать, сгруппированные по поставщикам.
        $itemsNeedingPurchase = [];     // Список товаров, для которых обнаружен дефицит (для ответа фронтенду).
        $createdPurchaseOrders = [];    // Список успешно созданных Purchase Orders (для ответа фронтенду).

        // Получаем ID поставщика по умолчанию из переменных окружения.
        $defaultVendorId = env('ZOHO_DEFAULT_VENDOR_ID');

        // Проверяем, что ID поставщика по умолчанию установлен. Без него мы не можем создавать PO.
        if (empty($defaultVendorId)) {
            Log::error('Переменная окружения ZOHO_DEFAULT_VENDOR_ID не установлена. Невозможно создать заказы на закупку.');
            // Можно вернуть ошибку, если создание PO обязательно при дефиците.
            // return response()->json([
            //     'success' => false,
            //     'message' => 'Ошибка конфигурации: не указан ID поставщика по умолчанию для заказов на закупку.'
            // ], 500);
        }

        // Если пользователь выбрал создавать заказы на закупку при дефиците И defaultVendorId установлен,
        // начинаем проверку остатков и подготовку данных для PO.
        if ($createPurchaseOrdersForDeficit && !empty($defaultVendorId)) {
            Log::info('Флаг создания заказов на закупку установлен. Начинаем проверку остатков.');
            foreach ($salesOrderData['line_items'] as $item) {
                $itemId = $item['item_id'];
                $orderedQuantity = $item['quantity'];

                // Запрашиваем актуальную информацию о товаре из Zoho Inventory.
                // Используем getItems с фильтром по item_ids, чтобы получить последние данные об остатках.
                $zohoItemData = $this->zohoItemService->getItems(['item_ids' => $itemId]);
                $zohoItemDetails = $zohoItemData['items'][0] ?? null; // Берем первый (и единственный) найденный товар

                $availableStock = 0;
                // Суммируем доступный остаток по всем локациям (если они есть).
                if ($zohoItemDetails && isset($zohoItemDetails['locations']) && is_array($zohoItemDetails['locations'])) {
                    foreach ($zohoItemDetails['locations'] as $location) {
                        $availableStock += (int) ($location['location_available_stock'] ?? 0);
                    }
                }

                // Если заказанное количество превышает доступный остаток, регистрируем дефицит.
                if ($orderedQuantity > $availableStock) {
                    $deficit = $orderedQuantity - $availableStock;
                    Log::info('Обнаружен дефицит товара. Добавляем в очередь на создание заказа на закупку.', [
                        'item_id' => $itemId,
                        'item_name' => $zohoItemDetails['name'] ?? 'N/A', // Имя товара для логов
                        'ordered_quantity' => $orderedQuantity,
                        'available_stock' => $availableStock,
                        'deficit' => $deficit
                    ]);

                    // Группируем товары по поставщику (в данном случае, по defaultVendorId).
                    // Если для данного поставщика еще нет записи в $purchaseOrdersToCreate, создаем её.
                    if (!isset($purchaseOrdersToCreate[$defaultVendorId])) {
                        $purchaseOrdersToCreate[$defaultVendorId] = [
                            'vendor_id' => $defaultVendorId,
                            'date' => Carbon::now()->format('Y-m-d'), // Текущая дата для PO
                            'delivery_date' => Carbon::now()->addWeeks(2)->format('Y-m-d'), // Ориентировочная дата доставки (через 2 недели)
                        ];
                    }

                    // Добавляем позицию товара в заказ на закупку.
                    $purchaseOrdersToCreate[$defaultVendorId]['line_items'][] = [
                        'item_id' => $itemId,
                        'quantity' => $deficit,
                        // Используем закупочную цену товара, если она доступна, иначе цену продажи.
                        'rate' => $zohoItemDetails['purchase_rate'] ?? $item['rate'],
                        // 'description' => $zohoItemDetails['description'] ?? '', // Опционально
                    ];

                    // Добавляем информацию о дефицитном товаре для возврата на фронтенд.
                    $itemsNeedingPurchase[] = [
                        'item_id' => $itemId,
                        'item_name' => $zohoItemDetails['name'] ?? 'Неизвестный товар',
                        'ordered_quantity' => $orderedQuantity,
                        'available_stock' => $availableStock,
                        'deficit' => $deficit,
                    ];
                }
            }

            // После проверки всех позиций, если есть товары, требующие заказа на закупку, создаем PO.
            if (!empty($purchaseOrdersToCreate)) {
                Log::info('Инициировано создание заказов на закупку Zoho.', ['count' => count($purchaseOrdersToCreate)]);
                foreach ($purchaseOrdersToCreate as $poData) {
                    $createdPo = $this->zohoPurchaseOrderService->createPurchaseOrder($poData);
                    if ($createdPo) {
                        $createdPurchaseOrders[] = $createdPo;
                        Log::info('Заказ на закупку Zoho успешно создан для поставщика.', ['vendor_id' => $poData['vendor_id'], 'purchaseorder_id' => $createdPo['purchaseorder_id']]);
                    } else {
                        Log::error('Не удалось создать заказ на закупку Zoho для поставщика.', ['vendor_id' => $poData['vendor_id'], 'po_data' => $poData]);
                        // Здесь можно добавить дополнительную логику обработки, например,
                        // уведомление администратора или возврат частичной ошибки.
                    }
                }
            } else {
                Log::info('Недостатка товара не обнаружено, заказы на закупку не требуются.');
            }
        } else {
            Log::info('Флаг создания заказа на закупку не установлен или ID поставщика по умолчанию отсутствует. Проверка остатков и создание PO пропущены.');
        }

        // Вне зависимости от логики создания PO, приступаем к созданию Sales Order.
        $salesOrder = $this->zohoSalesOrderService->createSalesOrder($salesOrderData);

        // Формируем ответ клиенту.
        if ($salesOrder) {
            Log::info('Заказ на продажу Zoho успешно создан через API.', ['salesorder_id' => $salesOrder['salesorder_id']]);

            $responseMessage = 'Заказ на продажу успешно создан в Zoho Inventory.';
            if ($createPurchaseOrdersForDeficit && !empty($createdPurchaseOrders)) {
                // Сообщение, если PO были запрошены и успешно созданы.
                $responseMessage .= ' Также были автоматически созданы заказы на закупку для недостающих товаров.';
            } elseif ($createPurchaseOrdersForDeficit && empty($createdPurchaseOrders) && !empty($itemsNeedingPurchase)) {
                // Сообщение, если PO были запрошены, но не удалось создать некоторые/все.
                $responseMessage .= ' Заказ на продажу создан, но не удалось создать некоторые заказы на закупку. Проверьте логи бэкенда.';
            }

            return response()->json([
                'success' => true,
                'message' => $responseMessage,
                'sales_order' => $salesOrder,
                'created_purchase_orders' => $createdPurchaseOrders, // Возвращаем созданные PO (может быть пустым)
                'items_needing_purchase' => $itemsNeedingPurchase // Возвращаем список дефицитных товаров (может быть пустым)
            ], 201); // 201 Created - статус успешного создания ресурса
        } else {
            // Если Sales Order не был создан, это критическая ошибка.
            Log::error('Не удалось создать заказ на продажу Zoho через API.', ['request_data' => $salesOrderData]);
            // В этом сценарии, если PO были успешно созданы ранее, возможно, потребуется их отмена.
            // Текущая логика этого не предусматривает, это усложнило бы код.
            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать заказ на продажу в Zoho Inventory. Проверьте логи бэкенда.'
            ], 500); // 500 Internal Server Error
        }
    }
}

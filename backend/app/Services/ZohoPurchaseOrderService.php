<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с заказами на закупку (Purchase Orders) в Zoho Inventory.
 *
 * Наследует ZohoBaseApiService для выполнения HTTP-запросов к Zoho API.
 */
class ZohoPurchaseOrderService extends ZohoBaseApiService
{
    /**
     * Создает новый заказ на закупку (Purchase Order) в Zoho Inventory.
     * Zoho Inventory API: POST /purchaseorders
     *
     * @param array $purchaseOrderData Данные заказа на закупку, соответствующие формату API Zoho Inventory.
     * Должны включать 'vendor_id' и 'line_items'.
     * @return array|null Созданный заказ на закупку (с его ID) или null в случае ошибки.
     */
    public function createPurchaseOrder(array $purchaseOrderData): ?array
    {
        // Zoho API требует, чтобы данные заказа на закупку были обернуты в ключ 'purchaseorder'.
        $requestData = ['purchaseorder' => $purchaseOrderData];

        // Выполняем POST-запрос к API заказов на закупку.
        $response = $this->zohoApiPost('/inventory/api/v1/purchaseorders', $requestData);

        // Проверяем, что заказ успешно создан и его данные возвращены.
        if ($response && isset($response['purchaseorder'])) {
            Log::info('Successfully created Zoho Purchase Order.', ['purchaseorder_id' => $response['purchaseorder']['purchaseorder_id']]);
            return $response['purchaseorder'];
        }

        // Логируем ошибку, если создание заказа на закупку не удалось.
        Log::error('Failed to create Zoho Purchase Order.', ['response' => $response, 'requestData' => $requestData]);
        return null;
    }
}

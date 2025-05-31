<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с заказами на продажу (Sales Orders) в Zoho Inventory.
 *
 * Наследует ZohoBaseApiService для выполнения HTTP-запросов к Zoho API.
 */
class ZohoSalesOrderService extends ZohoBaseApiService
{
    /**
     * Создает новый заказ на продажу (Sales Order) в Zoho Inventory.
     * Zoho Inventory API: POST /salesorders
     *
     * @param array $salesOrderData Данные заказа на продажу, соответствующие формату API Zoho Inventory.
     * Должны включать 'customer_id' и 'line_items'.
     * @return array|null Созданный заказ на продажу (с его ID) или null в случае ошибки.
     */
    public function createSalesOrder(array $salesOrderData): ?array
    {
        // Zoho API требует, чтобы данные заказа на продажу были обернуты в ключ 'salesorder'.
        $requestData = ['salesorder' => $salesOrderData];

        // Выполняем POST-запрос к API заказов на продажу.
        $response = $this->zohoApiPost('/inventory/api/v1/salesorders', $requestData);

        // Проверяем, что заказ успешно создан и его данные возвращены.
        if ($response && isset($response['salesorder'])) {
            Log::info('Successfully created Zoho Sales Order.', ['salesorder_id' => $response['salesorder']['salesorder_id']]);
            return $response['salesorder'];
        }

        // Логируем ошибку, если создание заказа на продажу не удалось.
        Log::error('Failed to create Zoho Sales Order.', ['response' => $response, 'requestData' => $requestData]);
        return null;
    }
}

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
        // --- НОВЫЙ КОД ДЛЯ ЛОГИРОВАНИЯ И ОБРАБОТКИ ---
        Log::info('ZohoSalesOrderService: Входящие данные для создания Sales Order', ['salesOrderData' => $salesOrderData]);

        // Обработка line_items перед отправкой в Zoho
        $salesOrderData['line_items'] = array_map(function($item) {
            $zohoLineItem = [
                'item_id' => (string) $item['item_id'],      // Убедимся, что item_id всегда строка
                'quantity' => (float) $item['quantity'],    // Zoho часто ожидает float
                'rate' => (float) $item['rate'],          // Zoho часто ожидает float
            ];

            // Если есть discount_amount, добавляем его
            if (isset($item['discount_amount']) && is_numeric($item['discount_amount']) && $item['discount_amount'] > 0) {
                $zohoLineItem['discount'] = (float) $item['discount_amount']; // <-- ИЗМЕНЕНО
                // Поле 'discount_type' не нужно, так как мы всегда отправляем сумму
            } else {
                // Логируем, если discount_amount отсутствует или не является числом > 0
                Log::info('ZohoSalesOrderService: discount_amount отсутствует или невалиден для позиции.', [
                    'item_id' => $item['item_id'] ?? 'N/A',
                    'discount_amount' => $item['discount_amount'] ?? 'N/A' // Логируем discount_amount
                ]);
            }

            // Добавляем другие поля, если они есть и нужны для Zoho API
            if (isset($item['description'])) {
                $zohoLineItem['description'] = (string) $item['description'];
            }
            // ... другие поля, которые вы хотите включить ...

            Log::info('ZohoSalesOrderService: Анализ line_item', [
                'original_item' => $item,
                'transformed_zoho_line_item' => $zohoLineItem
            ]);

            return $zohoLineItem;
        }, $salesOrderData['line_items'] ?? []);
        // --- КОНЕЦ НОВОГО КОДА ДЛЯ ЛОГИРОВАНИЯ И ОБРАБОТКИ ---


        $response = $this->zohoApiPost('/inventory/v1/salesorders', $salesOrderData);

        if ($response && isset($response['salesorder'])) {
            Log::info('Successfully created Zoho Sales Order.', ['salesorder_id' => $response['salesorder']['salesorder_id']]);
            return $response['salesorder'];
        }

        Log::error('Failed to create Zoho Sales Order.', ['response' => $response, 'requestData' => $salesOrderData]);
        return null;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ZohoSalesOrderService extends ZohoBaseApiService
{
    /**
     * Создать новый заказ на продажу в Zoho Inventory.
     */
    public function createSalesOrder(array $salesOrderData): ?array
    {
        Log::info('Входящие данные для создания Sales Order.', ['salesOrderData' => $salesOrderData]);

        // Обработка позиций заказа перед отправкой в Zoho.
        $salesOrderData['line_items'] = array_map(function($item) {
            $zohoLineItem = [
                'item_id' => (string) $item['item_id'],
                'quantity' => (float) $item['quantity'],
                'rate' => (float) $item['rate'],
            ];

            // Добавить скидку, если указана.
            if (isset($item['discount_amount']) && is_numeric($item['discount_amount']) && $item['discount_amount'] > 0) {
                $zohoLineItem['discount'] = (float) $item['discount_amount'];
            } else {
                Log::info('Сумма скидки отсутствует или невалидна для позиции.', [
                    'item_id' => $item['item_id'] ?? 'N/A',
                    'discount_amount' => $item['discount_amount'] ?? 'N/A'
                ]);
            }

            // Добавить описание, если есть.
            if (isset($item['description'])) {
                $zohoLineItem['description'] = (string) $item['description'];
            }

            Log::info('Анализ позиции заказа.', [
                'original_item' => $item,
                'transformed_zoho_line_item' => $zohoLineItem
            ]);

            return $zohoLineItem;
        }, $salesOrderData['line_items'] ?? []);

        $response = $this->zohoApiPost('/inventory/v1/salesorders', $salesOrderData);

        if ($response && isset($response['salesorder'])) {
            Log::info('Successfully created Zoho Sales Order.', ['salesorder_id' => $response['salesorder']['salesorder_id']]);
            return $response['salesorder'];
        }

        Log::error('Failed to create Zoho Sales Order.', ['response' => $response, 'requestData' => $salesOrderData]);
        return null;
    }
}

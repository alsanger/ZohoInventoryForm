<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ZohoItemService extends ZohoBaseApiService
{
    /**
     * Получить список товаров из Zoho Inventory.
     */
    public function getItems(array $filters = []): array
    {
        $query = array_merge([
            'per_page' => 50,
            'page' => 1,
            'sort_column' => 'name',
            'sort_order' => 'A',
        ], $filters);

        $response = $this->zohoApiGet('/inventory/v1/items', $query);

        if ($response && isset($response['items'])) {
            // Преобразовать 'available_for_sale' в int.
            $items = array_map(function($item) {
                $item['available_for_sale'] = (int) ($item['available_for_sale'] ?? 0);
                return $item;
            }, $response['items']);

            Log::info('Товары Zoho успешно получены.', ['count' => count($items), 'page' => $query['page']]);
            return [
                'items' => $items,
                'page_context' => $response['page_context'] ?? []
            ];
        }

        Log::error('Не удалось получить товары Zoho.', ['response' => $response, 'filters' => $filters]);
        return ['items' => [], 'page_context' => []];
    }

    /**
     * Получить детальную информацию о товаре по ID.
     */
    public function getItemDetails(string $itemId): ?array
    {
        if (empty($itemId)) {
            Log::error('Попытка получить детали товара Zoho без ID.');
            return null;
        }

        $endpoint = '/inventory/v1/items/' . $itemId;

        $response = $this->zohoApiGet($endpoint);

        if ($response && isset($response['item'])) {
            // Преобразовать 'available_for_sale' в int.
            $item = $response['item'];
            $item['available_for_sale'] = (int) ($item['available_for_sale'] ?? 0);
            Log::info('Детали товара Zoho успешно получены.', ['item_id' => $itemId, 'available_for_sale' => $item['available_for_sale']]);
            return $item;
        }

        Log::error('Не удалось получить детали товара Zoho.', ['item_id' => $itemId, 'response' => $response]);
        return null;
    }
}

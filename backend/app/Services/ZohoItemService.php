<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с товарами в Zoho Inventory.
 *
 * Наследует ZohoBaseApiService для выполнения HTTP-запросов к Zoho API.
 */
class ZohoItemService extends ZohoBaseApiService
{
    /**
     * Получает список товаров из Zoho Inventory.
     * Zoho Inventory API: GET /items
     *
     * @param array $filters Дополнительные фильтры и параметры (например, 'name', 'per_page', 'page').
     * @return array Массив товаров и информация о пагинации. Возвращает пустой массив с пустым page_context в случае ошибки.
     */
    public function getItems(array $filters = []): array
    {
        // Определяем параметры запроса по умолчанию и объединяем их с переданными фильтрами.
        $query = array_merge([
            'per_page' => 50, // Количество товаров на страницу по умолчанию
            'page' => 1,      // Номер страницы по умолчанию
            'sort_column' => 'name', // Сортировка по имени товара
            'sort_order' => 'A',   // Порядок сортировки: по возрастанию
        ], $filters);

        // Выполняем GET-запрос к API товаров.
        $response = $this->zohoApiGet('/inventory/v1/items', $query);

        // Проверяем, что ответ получен и содержит список товаров.
        if ($response && isset($response['items'])) {
            // Zoho Inventory API может возвращать 'available_for_sale' как строку,
            // преобразуем её в целое число для удобства работы.
            $items = array_map(function($item) {
                $item['available_for_sale'] = (int) ($item['available_for_sale'] ?? 0);
                return $item;
            }, $response['items']);

            Log::info('Successfully fetched Zoho items.', ['count' => count($items), 'page' => $query['page']]);
            return [
                'items' => $items,
                'page_context' => $response['page_context'] ?? [] // Информация о пагинации
            ];
        }

        // Логируем ошибку, если товары не удалось получить.
        Log::error('Failed to fetch Zoho items.', ['response' => $response, 'filters' => $filters]);
        // В случае ошибки возвращаем пустые данные.
        return ['items' => [], 'page_context' => []];
    }

    /**
     * Получает детальную информацию о конкретном товаре по его ID.
     * Zoho Inventory API: GET /items/{item_id}
     *
     * @param string $itemId Уникальный ID товара.
     * @return array|null Детали товара или null в случае ошибки.
     */
    public function getItemDetails(string $itemId): ?array
    {
        if (empty($itemId)) {
            Log::error('Attempt to get Zoho item details without an item ID.');
            return null;
        }

        // Формируем endpoint с ID товара.
        // zohoApiGet автоматически добавит organization_id как query-параметр.
        $endpoint = '/inventory/v1/items/' . $itemId;

        $response = $this->zohoApiGet($endpoint);

        // Проверяем, что ответ получен и содержит данные товара.
        if ($response && isset($response['item'])) {
            // Zoho Inventory API может возвращать 'available_for_sale' как строку,
            // преобразуем её в целое число для удобства работы, если оно есть.
            $item = $response['item'];
            $item['available_for_sale'] = (int) ($item['available_for_sale'] ?? 0);
            Log::info('Successfully fetched Zoho item details.', ['item_id' => $itemId, 'available_for_sale' => $item['available_for_sale']]);
            return $item;
        }

        Log::error('Failed to fetch Zoho item details.', ['item_id' => $itemId, 'response' => $response]);
        return null;
    }
}

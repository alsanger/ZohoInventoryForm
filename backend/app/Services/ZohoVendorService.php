<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с поставщиками (вендорами) в Zoho Inventory.
 *
 * Наследует ZohoBaseApiService для выполнения HTTP-запросов к Zoho API.
 */
class ZohoVendorService extends ZohoBaseApiService
{
    /**
     * Получает список поставщиков из Zoho Inventory, используя эндпоинт /contacts
     * и фильтруя по contact_type=vendor.
     * Zoho Inventory API: GET /contacts
     *
     * @param array $filters Дополнительные фильтры и параметры (например, 'search_text', 'per_page', 'page').
     * @return array Массив поставщиков и информация о пагинации. Возвращает пустой массив с пустым page_context в случае ошибки.
     */
    public function getVendors(array $filters = []): array
    {
        // Определяем параметры запроса по умолчанию, добавляем contact_type=vendor
        // и объединяем их с переданными фильтрами.
        $query = array_merge([
            'per_page' => 200, // Увеличиваем количество на страницу, чтобы получить всех сразу, если их не очень много
            'page' => 1,
            'sort_column' => 'contact_name', // Сортировка по имени контакта
            'sort_order' => 'A',
            'contact_type' => 'vendor', // !!! Ключевое изменение: фильтруем по типу контакта
        ], $filters);

        Log::info('Attempting to fetch Zoho vendors (via contacts API) with query:', $query);

        $response = $this->zohoApiGet('/inventory/v1/contacts', $query);

        // Логируем полный ответ от Zoho API
        Log::info('Raw response from Zoho API for vendors (via contacts API):', ['response' => $response]);

        if ($response && isset($response['contacts'])) {
            Log::info('Successfully fetched Zoho vendors (via contacts API).', ['count' => count($response['contacts']), 'page' => $query['page']]);
            return [
                'vendors' => $response['contacts'], // Возвращаем под ключом 'vendors' для совместимости с фронтендом
                'page_context' => $response['page_context'] ?? []
            ];
        }

        Log::error('Failed to fetch Zoho vendors (via contacts API) or "contacts" key is missing.', ['response' => $response, 'filters' => $filters]);
        return ['vendors' => [], 'page_context' => []];
    }

    /**
     * Получает детальную информацию о конкретном поставщике по его ID.
     * Zoho Inventory API: GET /contacts/{contact_id}
     *
     * @param string $vendorId Уникальный ID поставщика (теперь это contact_id).
     * @return array|null Детали поставщика или null в случае ошибки.
     */
    public function getVendorDetails(string $vendorId): ?array
    {
        if (empty($vendorId)) {
            Log::error('Attempt to get Zoho vendor details (via contacts API) without an ID.');
            return null;
        }

        $endpoint = '/inventory/v1/contacts/' . $vendorId;

        $response = $this->zohoApiGet($endpoint);

        if ($response && isset($response['contact'])) {
            Log::info('Successfully fetched Zoho vendor details (via contacts API).', ['vendor_id' => $vendorId]);
            return $response['contact'];
        }

        Log::error('Failed to fetch Zoho vendor details (via contacts API).', ['vendor_id' => $vendorId, 'response' => $response]);
        return null;
    }
}

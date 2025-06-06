<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ZohoVendorService extends ZohoBaseApiService
{
    /**
     * Получить список поставщиков из Zoho Inventory.
     */
    public function getVendors(array $filters = []): array
    {
        // Фильтруем по типу контакта 'vendor'.
        $query = array_merge([
            'per_page' => 200,
            'page' => 1,
            'sort_column' => 'contact_name',
            'sort_order' => 'A',
            'contact_type' => 'vendor',
        ], $filters);

        Log::info('Попытка получить поставщиков Zoho (через API контактов) с запросом:', $query);

        $response = $this->zohoApiGet('/inventory/v1/contacts', $query);

        Log::info('Исходный ответ Zoho API для поставщиков (через API контактов):', ['response' => $response]);

        if ($response && isset($response['contacts'])) {
            Log::info('Поставщики Zoho успешно получены (через API контактов).', ['count' => count($response['contacts']), 'page' => $query['page']]);
            return [
                'vendors' => $response['contacts'],
                'page_context' => $response['page_context'] ?? []
            ];
        }

        Log::error('Не удалось получить поставщиков Zoho (через API контактов) или отсутствует ключ "contacts".', ['response' => $response, 'filters' => $filters]);
        return ['vendors' => [], 'page_context' => []];
    }

    /**
     * Получить детальную информацию о поставщике по ID.
     */
    public function getVendorDetails(string $vendorId): ?array
    {
        if (empty($vendorId)) {
            Log::error('Попытка получить детали поставщика Zoho (через API контактов) без ID.');
            return null;
        }

        $endpoint = '/inventory/v1/contacts/' . $vendorId;

        $response = $this->zohoApiGet($endpoint);

        if ($response && isset($response['contact'])) {
            Log::info('Детали поставщика Zoho успешно получены (через API контактов).', ['vendor_id' => $vendorId]);
            return $response['contact'];
        }

        Log::error('Не удалось получить детали поставщика Zoho (через API контактов).', ['vendor_id' => $vendorId, 'response' => $response]);
        return null;
    }
}

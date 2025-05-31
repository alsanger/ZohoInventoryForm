<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с данными организации в Zoho Inventory.
 *
 * Наследует ZohoBaseApiService для выполнения HTTP-запросов к Zoho API.
 */
class ZohoOrganizationService extends ZohoBaseApiService
{
    /**
     * Получает детали организации из Zoho Inventory.
     * Zoho Inventory API: GET /organization
     *
     * @return array|null Детали организации (например, 'organization_name', 'organization_id')
     * или null в случае ошибки.
     */
    public function getOrganizationDetails(): ?array
    {
        // Используем вспомогательный метод zohoApiGet из базового класса.
        $response = $this->zohoApiGet('/inventory/api/v1/organization');

        // Проверяем, что ответ получен и содержит данные организации.
        if ($response && isset($response['organization'])) {
            Log::info('Successfully fetched Zoho organization details.', ['org_id' => $response['organization']['organization_id']]);
            return $response['organization'];
        }

        // Логируем ошибку, если данные организации не удалось получить.
        Log::error('Failed to fetch Zoho organization details.', ['response' => $response]);
        return null;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ZohoContactService extends ZohoBaseApiService
{
    /**
     * Получить список контактов из Zoho Inventory.
     */
    public function getContacts(string $contactType = '', array $filters = []): array
    {
        $query = array_merge([
            'per_page' => 200,
            'page' => 1,
            'sort_column' => 'contact_name',
            'sort_order' => 'A',
        ], $filters);

        if (!empty($contactType)) {
            $query['contact_type'] = $contactType;
        }

        $response = $this->zohoApiGet('/inventory/v1/contacts', $query);

        if ($response && isset($response['contacts'])) {
            Log::info('Контакты Zoho успешно получены.', ['count' => count($response['contacts'])]);
            return $response['contacts'];
        }

        Log::error('Не удалось получить контакты Zoho.', ['response' => $response, 'query' => $query]);
        return [];
    }

    /**
     * Создать новый контакт в Zoho Inventory.
     */
    public function createContact(array $contactData): ?array
    {
        // Проверка обязательного поля.
        if (!isset($contactData['contact_name']) || empty($contactData['contact_name'])) {
            Log::error('Попытка создать контакт Zoho без имени.', ['data' => $contactData]);
            return null;
        }

        // Установка типа контакта по умолчанию, если не указан.
        if (!isset($contactData['contact_type']) || empty($contactData['contact_type'])) {
            $contactData['contact_type'] = 'customer';
            Log::info('Тип контакта не указан, установлен по умолчанию: "customer".', ['final_contact_type' => $contactData['contact_type']]);
        } else {
            Log::info('Тип контакта получен из запроса.', ['received_contact_type' => $contactData['contact_type']]);
        }

        $requestData = $contactData;

        Log::debug('Отправка запроса на создание контакта в Zoho API.', ['payload' => $requestData]);

        $response = $this->zohoApiPost('/inventory/v1/contacts', $requestData);

        if ($response && isset($response['contact'])) {
            Log::info('Контакт Zoho успешно создан.', ['contact_id' => $response['contact']['contact_id']]);
            return $response['contact'];
        }

        Log::error('Не удалось создать контакт Zoho.', ['response' => $response, 'requestData' => $requestData]);
        return null;
    }
}

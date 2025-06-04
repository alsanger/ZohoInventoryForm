<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с контактами (клиентами и поставщиками) в Zoho Inventory.
 *
 * Наследует ZohoBaseApiService для выполнения HTTP-запросов к Zoho API.
 */
class ZohoContactService extends ZohoBaseApiService
{
    /**
     * Получает список контактов из Zoho Inventory.
     * Zoho Inventory API: GET /contacts
     *
     * @param string $contactType Тип контакта для фильтрации (например, 'customer', 'vendor' или пустая строка для всех).
     * @param array $filters Дополнительные параметры запроса (например, 'per_page', 'page', 'sort_column').
     * @return array Массив контактов или пустой массив в случае ошибки.
     */
    public function getContacts(string $contactType = '', array $filters = []): array
    {
        $query = array_merge([
            'per_page' => 200,      // Количество записей на страницу (можно увеличить при необходимости)
            'page' => 1,            // Номер страницы
            'sort_column' => 'contact_name', // Сортировка по имени контакта
            'sort_order' => 'A',  // Порядок сортировки: по возрастанию
        ], $filters);

        // Добавляем фильтр по типу контакта, если он указан.
        if (!empty($contactType)) {
            $query['contact_type'] = $contactType;
        }

        // Выполняем GET-запрос к API контактов.
        $response = $this->zohoApiGet('/inventory/v1/contacts', $query);

        // Проверяем, что ответ получен и содержит список контактов.
        if ($response && isset($response['contacts'])) {
            Log::info('Successfully fetched Zoho contacts.', ['count' => count($response['contacts'])]);
            return $response['contacts'];
        }

        // Логируем ошибку, если контакты не удалось получить.
        Log::error('Failed to fetch Zoho contacts.', ['response' => $response, 'query' => $query]);
        return [];
    }

    /**
     * Создает новый контакт (клиента или поставщика) в Zoho Inventory.
     * Zoho Inventory API: POST /contacts
     *
     * @param array $contactData Данные нового контакта (например, ['contact_name' => 'Имя Клиента', 'contact_type' => 'customer']).
     * Обязательно должно содержать 'contact_name'.
     * @return array|null Созданный контакт (с его ID) или null в случае ошибки.
     */
    public function createContact(array $contactData): ?array
    {
        // Проверяем наличие обязательного поля contact_name.
        if (!isset($contactData['contact_name']) || empty($contactData['contact_name'])) {
            Log::error('Attempt to create Zoho contact without contact_name.', ['data' => $contactData]);
            return null;
        }

        // Zoho API требует, чтобы данные контакта были обернуты в ключ 'contact'.
        $requestData = ['contact' => $contactData];

        // Выполняем POST-запрос к API контактов.
        $response = $this->zohoApiPost('/inventory/v1/contacts', $requestData);

        // Проверяем, что контакт успешно создан и его данные возвращены.
        if ($response && isset($response['contact'])) {
            Log::info('Successfully created Zoho contact.', ['contact_id' => $response['contact']['contact_id']]);
            return $response['contact'];
        }

        // Логируем ошибку, если создание контакта не удалось.
        Log::error('Failed to create Zoho contact.', ['response' => $response, 'requestData' => $requestData]);
        return null;
    }
}

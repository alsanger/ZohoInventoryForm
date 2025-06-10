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

        // Данные для первоначального создания контакта (только contact_name и contact_type)
        // Остальные данные будут добавлены через PUT-запрос
        $initialCreatePayload = [
            'contact_name' => $contactData['contact_name'],
            'contact_type' => $contactData['contact_type'],
        ];

        Log::debug('Отправка запроса на создание базового контакта в Zoho API.', ['payload' => $initialCreatePayload]);

        $response = $this->zohoApiPost('/inventory/v1/contacts', $initialCreatePayload);

        // Если базовый контакт успешно создан
        if ($response && isset($response['contact'])) {
            $contactId = $response['contact']['contact_id'];
            $createdContactData = $response['contact']; // Сохраняем данные только что созданного контакта

            Log::info('Базовый контакт Zoho успешно создан.', ['contact_id' => $contactId]);

            // Подготавливаем данные для обновления контакта
            $updatePayload = [];

            $contactPersons = [];
            if (isset($contactData['email']) && !empty($contactData['email'])) {
                $contactPersons['email'] = $contactData['email'];
            }
            if (isset($contactData['phone']) && !empty($contactData['phone'])) {
                $contactPersons['phone'] = $contactData['phone'];
            }
            if (!empty($contactPersons)) {
                $contactPersons['is_primary_contact'] = true; // Устанавливаем как основное контактное лицо
                $updatePayload['contact_persons'] = [$contactPersons];
                Log::debug('Подготовлены данные контактного лица для обновления.', ['contact_persons' => $updatePayload['contact_persons']]);
            }

            if (isset($contactData['company_name']) && !empty($contactData['company_name'])) {
                $updatePayload['company_name'] = $contactData['company_name'];
                Log::debug('Добавлено название компании для обновления.', ['company_name' => $contactData['company_name']]);
            }

            if (isset($contactData['shipping_address']) && is_array($contactData['shipping_address'])) {
                $updatePayload['shipping_address'] = $contactData['shipping_address'];
                Log::debug('Добавлен адрес доставки для обновления.', ['shipping_address' => $contactData['shipping_address']]);
            }

            // Если есть данные для обновления, выполняем PUT запрос
            if (!empty($updatePayload)) {
                Log::debug('Отправка запроса на обновление контакта в Zoho API.', ['contact_id' => $contactId, 'payload' => $updatePayload]);

                // Используем zohoApiPut из родительского класса
                $updateResponse = $this->zohoApiPut("/inventory/v1/contacts/{$contactId}", $updatePayload);

                if ($updateResponse && isset($updateResponse['contact'])) {
                    Log::info('Контакт Zoho успешно обновлен с дополнительными данными.', ['contact_id' => $contactId]);
                    return $updateResponse['contact']; // Возвращаем обновленный контакт
                } else {
                    Log::error('Не удалось обновить контакт Zoho после создания. Контакт создан, но без деталей.', ['contact_id' => $contactId, 'updateResponse' => $updateResponse, 'updateData' => $updatePayload]);
                    // Если обновление не удалось, возвращаем данные базового контакта
                    return $createdContactData;
                }
            } else {
                Log::info('Нет дополнительных данных для обновления контакта. Возвращаем базовый контакт.', ['contact_id' => $contactId]);
                // Если нет дополнительных данных для обновления, возвращаем только что созданный базовый контакт
                return $createdContactData;
            }

        }

        // Если базовый контакт не был создан
        Log::error('Не удалось создать базовый контакт Zoho.', ['response' => $response, 'requestData' => $initialCreatePayload]);
        return null;
    }
}

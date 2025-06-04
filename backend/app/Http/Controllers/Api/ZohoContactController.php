<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateZohoContactRequest;
use App\Services\ZohoAuthService;
use App\Services\ZohoContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер API для управления контактами в Zoho Inventory.
 *
 * Отвечает за получение списка контактов и создание новых контактов.
 */
class ZohoContactController extends Controller
{
    protected ZohoContactService $zohoContactService;
    protected ZohoAuthService $zohoAuthService; // Нужен для проверки токена в middleware

    /**
     * Конструктор ZohoContactController.
     * Инжектирует ZohoContactService и применяет middleware для проверки авторизации Zoho.
     *
     * @param ZohoContactService $zohoContactService
     * @param ZohoAuthService $zohoAuthService
     */
    public function __construct(ZohoContactService $zohoContactService, ZohoAuthService $zohoAuthService)
    {
        $this->zohoContactService = $zohoContactService;
        $this->zohoAuthService = $zohoAuthService;
    }

    /**
     * Получает список контактов (клиентов) из Zoho Inventory.
     * Поддерживает поиск по имени контакта.
     *
     * @param Request $request Параметры запроса, такие как 'search'.
     * @return JsonResponse Массив контактов.
     */
    public function index(Request $request): JsonResponse
    {
        Log::info("Попали в ZohoContactController@index");
        $search = $request->query('search');
        $filters = [];

        if (!empty($search)) {
            // Zoho API использует 'search_text' для полнотекстового поиска по контактам.
            $filters['search_text'] = $search;
        }

        $contacts = $this->zohoContactService->getContacts('customer', $filters);

        if (empty($contacts) && !empty($search)) {
            Log::info('No Zoho contacts found for search query.', ['search' => $search]);
        } elseif (empty($contacts)) {
            Log::warning('No Zoho contacts found during general fetch.');
        }

        return response()->json([
            'success' => true,
            'contacts' => $contacts
        ]);
    }

    /**
     * Создает новый контакт в Zoho Inventory.
     * Валидация данных выполняется классом CreateZohoContactRequest.
     *
     * @param CreateZohoContactRequest $request Валидированный запрос.
     * @return JsonResponse Результат операции.
     */
    public function store(CreateZohoContactRequest $request): JsonResponse
    {
        $contactData = $request->validated(); // Данные уже прошли валидацию

        $contact = $this->zohoContactService->createContact($contactData);

        if ($contact) {
            Log::info('New Zoho contact created successfully via API.', ['contact_id' => $contact['contact_id']]);
            return response()->json([
                'success' => true,
                'message' => 'Контакт успешно создан в Zoho Inventory.',
                'contact' => $contact
            ], 201); // 201 Created
        } else {
            Log::error('Failed to create Zoho contact via API.', ['request_data' => $contactData]);
            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать контакт в Zoho Inventory. Проверьте логи бэкенда.'
            ], 500); // 500 Internal Server Error
        }
    }
}

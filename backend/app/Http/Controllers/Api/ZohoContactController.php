<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateZohoContactRequest;
use App\Services\ZohoAuthService;
use App\Services\ZohoContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZohoContactController extends Controller
{
    protected ZohoContactService $zohoContactService;
    protected ZohoAuthService $zohoAuthService;

    public function __construct(ZohoContactService $zohoContactService, ZohoAuthService $zohoAuthService)
    {
        $this->zohoContactService = $zohoContactService;
        $this->zohoAuthService = $zohoAuthService;
    }

    /**
     * Получить список контактов из Zoho Inventory.
     */
    public function index(Request $request): JsonResponse
    {
        Log::info("Получение контактов Zoho.");
        $search = $request->query('search');
        $filters = [];

        if (!empty($search)) {
            $filters['search_text'] = $search;
        }

        $contacts = $this->zohoContactService->getContacts('customer', $filters);

        if (empty($contacts) && !empty($search)) {
            Log::info('Контакты Zoho не найдены по запросу.', ['search' => $search]);
        } elseif (empty($contacts)) {
            Log::warning('Контакты Zoho не найдены.');
        }

        return response()->json([
            'success' => true,
            'contacts' => $contacts
        ]);
    }

    /**
     * Создать новый контакт в Zoho Inventory.
     */
    public function store(CreateZohoContactRequest $request): JsonResponse
    {
        $contactData = $request->validated();

        $contact = $this->zohoContactService->createContact($contactData);

        if ($contact) {
            Log::info('New Zoho contact created successfully via API.', ['contact_id' => $contact['contact_id']]);
            return response()->json([
                'success' => true,
                'message' => 'Contact created successfully in Zoho Inventory.',
                'contact' => $contact
            ], 201);
        } else {
            Log::error('Не удалось создать контакт Zoho через API.', ['request_data' => $contactData]);
            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать контакт в Zoho Inventory. Проверьте логи бэкенда.'
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ZohoAuthService;
use App\Services\ZohoItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер API для управления товарами в Zoho Inventory.
 *
 * Отвечает за получение списка товаров.
 */
class ZohoItemController extends Controller
{
    protected ZohoItemService $zohoItemService;
    protected ZohoAuthService $zohoAuthService; // Нужен для проверки токена в middleware

    /**
     * Конструктор ZohoItemController.
     * Инжектирует ZohoItemService и применяет middleware для проверки авторизации Zoho.
     *
     * @param ZohoItemService $zohoItemService
     * @param ZohoAuthService $zohoAuthService
     */
    public function __construct(ZohoItemService $zohoItemService, ZohoAuthService $zohoAuthService)
    {
        $this->zohoItemService = $zohoItemService;
        $this->zohoAuthService = $zohoAuthService;

        // Применяем middleware ко всем методам этого контроллера.
        // Он проверит наличие действительного токена Zoho. Если токен недействителен,
        // вернет JSON-ответ 401 Unauthorized.
        $this->middleware(function (Request $request, $next) {
            if (!$this->zohoAuthService->getToken()) {
                Log::warning('ZohoItemController: Access denied. No valid Zoho token found.');
                return response()->json([
                    'success' => false,
                    'message' => 'Требуется авторизация Zoho Inventory. Пожалуйста, авторизуйтесь.'
                ], 401); // 401 Unauthorized
            }
            return $next($request);
        });
    }

    /**
     * Получает список товаров из Zoho Inventory.
     * Поддерживает поиск по названию товара.
     *
     * @param Request $request Параметры запроса, такие как 'search'.
     * @return JsonResponse Массив товаров.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [];
        if ($request->has('search') && !empty($request->input('search'))) {
            // Zoho API использует 'search_text' для полнотекстового поиска по товарам.
            $filters['search_text'] = $request->input('search');
        }

        $itemsData = $this->zohoItemService->getItems($filters);

        if (empty($itemsData['items']) && !empty($request->input('search'))) {
            Log::info('No Zoho items found for search query.', ['search' => $filters['search_text']]);
        } elseif (empty($itemsData['items'])) {
            Log::warning('No Zoho items found during general fetch.');
        }

        return response()->json([
            'success' => true,
            'items' => $itemsData['items'],
            'page_context' => $itemsData['page_context'] ?? [] // Пагинация от Zoho
        ]);
    }
}

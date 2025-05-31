<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ZohoAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер для общих утилит API.
 * Включает методы для получения CSRF-токена и проверки статуса авторизации Zoho.
 */
class UtilController extends Controller
{
    protected ZohoAuthService $zohoAuthService;

    /**
     * Конструктор UtilController.
     * Инжектирует ZohoAuthService для проверки статуса авторизации.
     *
     * @param ZohoAuthService $zohoAuthService
     */
    public function __construct(ZohoAuthService $zohoAuthService)
    {
        $this->zohoAuthService = $zohoAuthService;
    }

    /**
     * Возвращает CSRF-токен для фронтенда Vue.js.
     * Фронтенд должен получить этот токен перед отправкой POST/PUT/DELETE запросов.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCsrfToken(Request $request): JsonResponse
    {
        Log::info('CSRF token requested.');
        return response()->json([
            'csrf_token' => csrf_token()
        ]);
    }

    /**
     * Проверяет статус авторизации Zoho.
     * Используется фронтендом для определения, нужно ли отображать интерфейс
     * или кнопку "Авторизоваться с Zoho".
     *
     * @return JsonResponse
     */
    public function checkZohoAuthStatus(): JsonResponse
    {
        // getToken() внутри ZohoAuthService сам по себе возвращает токен или null,
        // что позволяет определить статус авторизации и при необходимости обновить токен.
        $isAuthenticated = (bool) $this->zohoAuthService->getToken();
        Log::info('Zoho auth status checked.', ['authenticated' => $isAuthenticated]);
        return response()->json(['authenticated' => $isAuthenticated]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ZohoAuthCallbackRequest; // Мы создадим этот Request позже
use App\Services\ZohoAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер для управления процессом аутентификации Zoho OAuth 2.0.
 *
 * Отвечает за:
 * 1. Генерацию URL для авторизации Zoho.
 * 2. Обработку callback-запроса от Zoho после успешной авторизации.
 */
class ZohoAuthController extends Controller
{
    protected ZohoAuthService $zohoAuthService;

    /**
     * Конструктор ZohoAuthController.
     * Автоматически инжектирует ZohoAuthService.
     *
     * @param ZohoAuthService $zohoAuthService
     */
    public function __construct(ZohoAuthService $zohoAuthService)
    {
        $this->zohoAuthService = $zohoAuthService;
    }

    /**
     * Возвращает URL для начала процесса авторизации Zoho OAuth 2.0.
     * Этот эндпоинт будет вызываться фронтендом Vue.js, когда потребуется авторизация.
     *
     * @return RedirectResponse
     */
    public function getZohoAuthUrl(): JsonResponse
    {
        $authUrl = $this->zohoAuthService->getAuthUrl();
        Log::info('Redirecting user to Zoho for authorization.', ['auth_url' => $authUrl]);
        return response()->json(['auth_url' => $authUrl]);
    }

    /**
     * Обрабатывает обратный вызов (callback) от Zoho OAuth авторизации.
     *
     * Принимает код авторизации, полученный от Zoho, обменивает его на токены
     * и сохраняет эти токены в базе данных.
     * После обработки, перенаправляет пользователя на фронтенд SPA,
     * передавая статус авторизации через URL-параметры или сессию.
     *
     * @param ZohoAuthCallbackRequest $request Объект запроса, валидированный ZohoAuthCallbackRequest.
     * @return RedirectResponse Перенаправление на базовый URL фронтенда с параметрами.
     */
    public function handleZohoCallback(ZohoAuthCallbackRequest $request): RedirectResponse
    {
        $code = $request->input('code');
        $location = $request->input('location', 'eu'); // Регион Zoho по умолчанию 'eu'.

        Log::info('Received Zoho auth callback.', ['code_present' => !empty($code), 'location' => $location]);

        $result = $this->zohoAuthService->processCallback($code, $location);

        // Определяем базовый URL нашего Vue.js фронтенда
        $frontendBaseUrl = env('ZOHO_FRONTEND_URL', 'http://localhost:5173');

        if ($result['success']) {
            Log::info('Zoho authorization successful, redirecting to frontend.', ['frontend_url' => $frontendBaseUrl]);
            // Перенаправляем на фронтенд, указывая на успешную авторизацию.
            // Фронтенд должен будет обработать эти параметры.
            return redirect()->away($frontendBaseUrl . '?auth_status=success&message=' . urlencode($result['message']));
        } else {
            Log::error('Zoho authorization failed, redirecting to frontend with error.', ['error_message' => $result['message']]);
            // Перенаправляем на фронтенд, указывая на ошибку.
            return redirect()->away($frontendBaseUrl . '?auth_status=error&message=' . urlencode($result['message']));
        }
    }
}

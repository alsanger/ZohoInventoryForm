<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ZohoAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UtilController extends Controller
{
    protected ZohoAuthService $zohoAuthService;

    public function __construct(ZohoAuthService $zohoAuthService)
    {
        $this->zohoAuthService = $zohoAuthService;
    }

    /**
     * Получить CSRF-токен для фронтенда.
     */
    public function getCsrfToken(Request $request): JsonResponse
    {
        Log::info('Запрос CSRF токена.');
        return response()->json([
            'csrf_token' => csrf_token()
        ]);
    }

    /**
     * Проверить статус авторизации Zoho.
     */
    public function checkZohoAuthStatus(): JsonResponse
    {
        $isAuthenticated = (bool) $this->zohoAuthService->getToken();
        Log::info('Статус авторизации Zoho проверен.', ['authenticated' => $isAuthenticated]);
        return response()->json(['authenticated' => $isAuthenticated]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ZohoAuthCallbackRequest;
use App\Services\ZohoAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class ZohoAuthController extends Controller
{
    protected ZohoAuthService $zohoAuthService;

    public function __construct(ZohoAuthService $zohoAuthService)
    {
        $this->zohoAuthService = $zohoAuthService;
    }

    /**
     * Получить URL для авторизации Zoho.
     */
    public function getZohoAuthUrl(): JsonResponse
    {
        $authUrl = $this->zohoAuthService->getAuthUrl();
        Log::info('Перенаправление на Zoho для авторизации.', ['auth_url' => $authUrl]);
        return response()->json(['auth_url' => $authUrl]);
    }

    /**
     * Обработка callback от Zoho OAuth.
     */
    public function handleZohoCallback(ZohoAuthCallbackRequest $request): RedirectResponse
    {
        $code = $request->input('code');
        $location = $request->input('location', 'eu');

        Log::info('Получен Zoho auth callback.', ['code_present' => !empty($code), 'location' => $location]);

        $result = $this->zohoAuthService->processCallback($code, $location);

        $frontendBaseUrl = env('ZOHO_FRONTEND_URL', 'http://localhost:5173');

        if ($result['success']) {
            Log::info('Zoho authorization successful, redirecting to frontend.', ['frontend_url' => $frontendBaseUrl]);
            return redirect()->away($frontendBaseUrl . '?auth_status=success&message=' . urlencode($result['message']));
        } else {
            Log::error('Zoho authorization failed, redirecting to frontend with error.', ['error_message' => $result['message']]);
            return redirect()->away($frontendBaseUrl . '?auth_status=error&message=' . urlencode($result['message']));
        }
    }
}

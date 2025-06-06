<?php

namespace App\Services;

use App\Models\ZohoToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $zohoAccountsDomain;

    public function __construct()
    {
        $this->clientId = env('ZOHO_CLIENT_ID');
        $this->clientSecret = env('ZOHO_CLIENT_SECRET');
        $this->redirectUri = env('ZOHO_REDIRECT_URI');
        $this->zohoAccountsDomain = env('ZOHO_ACCOUNTS_DOMAIN', 'https://accounts.zoho.eu');
    }

    /**
     * Возвращает URL для начала авторизации Zoho OAuth 2.0.
     */
    public function getAuthUrl(): string
    {
        return $this->zohoAccountsDomain . '/oauth/v2/auth?' . http_build_query([
                'scope' => 'ZohoInventory.FullAccess.all',
                'client_id' => $this->clientId,
                'response_type' => 'code',
                'access_type' => 'offline',
                'redirect_uri' => $this->redirectUri,
                'prompt' => 'consent'
            ]);
    }

    /**
     * Обрабатывает callback от Zoho OAuth.
     */
    public function processCallback(string $code, string $location = 'eu'): array
    {
        try {
            $accountsDomain = 'https://accounts.zoho.' . $location;

            $params = [
                'grant_type' => 'authorization_code',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'code' => $code,
            ];

            $response = Http::asForm()->post($accountsDomain . '/oauth/v2/token', $params);
            $data = $response->json();

            if (!$data || isset($data['error']) || !isset($data['access_token'])) {
                $errorMessage = $data['error'] ?? 'Error getting token';
                Log::error('Ошибка авторизации Zoho при callback', ['details' => $errorMessage, 'response' => $data]);
                return ['success' => false, 'message' => 'Ошибка авторизации: ' . $errorMessage];
            }

            // Удаляем старые токены и сохраняем новые.
            ZohoToken::truncate();
            ZohoToken::create([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_at' => Carbon::now()->addSeconds($data['expires_in']),
            ]);

            Log::info('Zoho Inventory authorization successful.');
            return ['success' => true, 'message' => 'Авторизация в Zoho Inventory прошла успешно!'];
        } catch (\Exception $e) {
            Log::error('Исключение при авторизации Zoho во время callback', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Ошибка авторизации: ' . $e->getMessage()];
        }
    }

    /**
     * Получает актуальный access_token.
     */
    public function getToken(): ?string
    {
        $token = ZohoToken::first();

        if (!$token) {
            Log::warning('Токен Zoho не найден. Требуется авторизация.');
            return null;
        }

        // Если токен истек или скоро истечет (в пределах 30 секунд), обновляем его.
        if ($token->expires_at->lt(Carbon::now()->addSeconds(30))) {
            Log::info('Access токен Zoho истек или скоро истечет, попытка обновления.');
            $newAccessToken = $this->refreshToken($token);
            if ($newAccessToken) {
                Log::info('Access токен Zoho успешно обновлен.');
            } else {
                Log::error('Не удалось обновить access токен Zoho. Возможно, потребуется повторная авторизация.');
            }
            return $newAccessToken;
        }

        return $token->access_token;
    }

    /**
     * Обновляет access_token Zoho с использованием refresh_token.
     */
    protected function refreshToken(ZohoToken $token): ?string
    {
        try {
            $params = [
                'grant_type' => 'refresh_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $token->refresh_token,
            ];

            $response = Http::asForm()->post($this->zohoAccountsDomain . '/oauth/v2/token', $params);
            $data = $response->json();

            if (!$data || isset($data['error']) || !isset($data['access_token'])) {
                Log::error('Ошибка обновления токена Zoho', ['error' => $data['error'] ?? 'Unknown error', 'response' => $data]);
                return null;
            }

            $token->access_token = $data['access_token'];
            $token->expires_at = Carbon::now()->addSeconds($data['expires_in']);
            $token->save();

            return $token->access_token;
        } catch (\Exception $e) {
            Log::error('Исключение при обновлении токена Zoho', ['error' => $e->getMessage()]);
            return null;
        }
    }
}

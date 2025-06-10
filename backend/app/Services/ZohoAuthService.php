<?php

namespace App\Services;

use App\Interfaces\ZohoTokenRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $zohoAccountsDomain;
    private ZohoTokenRepositoryInterface $tokenRepository;

    public function __construct(ZohoTokenRepositoryInterface $tokenRepository)
    {
        $this->clientId = env('ZOHO_CLIENT_ID');
        $this->clientSecret = env('ZOHO_CLIENT_SECRET');
        $this->redirectUri = env('ZOHO_REDIRECT_URI');
        $this->zohoAccountsDomain = env('ZOHO_ACCOUNTS_DOMAIN', 'https://accounts.zoho.eu');
        $this->tokenRepository = $tokenRepository;
    }

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

            // Используем репозиторий для сохранения токена
            $this->tokenRepository->clearTokens();
            $this->tokenRepository->saveToken(
                $data['access_token'],
                $data['refresh_token'],
                Carbon::now()->addSeconds($data['expires_in'])
            );

            Log::info('Zoho Inventory authorization successful.');
            return ['success' => true, 'message' => 'Авторизация в Zoho Inventory прошла успешно!'];
        } catch (\Exception $e) {
            Log::error('Исключение при авторизации Zoho во время callback', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Ошибка авторизации: ' . $e->getMessage()];
        }
    }

    public function getToken(): ?string
    {
        $token = $this->tokenRepository->getToken();

        if (!$token) {
            Log::warning('Токен Zoho не найден. Требуется авторизация.');
            return null;
        }

        if (Carbon::parse($token['expires_at'])->lt(Carbon::now()->addSeconds(30))) {
            Log::info('Access токен Zoho истек или скоро истечет, попытка обновления.');
            $newAccessToken = $this->refreshToken($token['refresh_token']);
            if ($newAccessToken) {
                Log::info('Access токен Zoho успешно обновлен.');
            } else {
                Log::error('Не удалось обновить access токен Zoho. Возможно, потребуется повторная авторизация.');
            }
            return $newAccessToken;
        }

        return $token['access_token'];
    }

    protected function refreshToken(string $refreshToken): ?string
    {
        try {
            $params = [
                'grant_type' => 'refresh_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $refreshToken,
            ];

            $response = Http::asForm()->post($this->zohoAccountsDomain . '/oauth/v2/token', $params);
            $data = $response->json();

            if (!$data || isset($data['error']) || !isset($data['access_token'])) {
                Log::error('Ошибка обновления токена Zoho', ['error' => $data['error'] ?? 'Unknown error', 'response' => $data]);
                return null;
            }

            // Обновляем токен через репозиторий
            $this->tokenRepository->updateToken(
                $data['access_token'],
                Carbon::now()->addSeconds($data['expires_in'])
            );

            return $data['access_token'];
        } catch (\Exception $e) {
            Log::error('Исключение при обновлении токена Zoho', ['error' => $e->getMessage()]);
            return null;
        }
    }
}

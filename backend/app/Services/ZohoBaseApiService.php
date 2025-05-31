<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Базовый абстрактный сервис для взаимодействия с Zoho API.
 *
 * Предоставляет вспомогательные методы для выполнения GET и POST запросов,
 * автоматически добавляя заголовок авторизации. Зависит от ZohoAuthService
 * для получения актуального токена.
 */
abstract class ZohoBaseApiService
{
    /**
     * Базовый домен для Zoho Inventory API (например, https://inventory.zoho.eu).
     * @var string
     */
    protected string $zohoApiDomain;

    /**
     * Экземпляр сервиса для работы с токенами Zoho.
     * @var ZohoAuthService
     */
    protected ZohoAuthService $zohoAuthService;

    /**
     * Конструктор ZohoBaseApiService.
     *
     * @param ZohoAuthService $zohoAuthService Автоматически инжектируется контейнером Laravel.
     */
    public function __construct(ZohoAuthService $zohoAuthService)
    {
        // Получаем базовый домен Zoho API из переменных окружения.
        // Используем дефолтное значение для региона EU, если переменная не установлена.
        $this->zohoApiDomain = env('ZOHO_API_DOMAIN', 'https://inventory.zoho.eu');
        $this->zohoAuthService = $zohoAuthService;
    }

    /**
     * Вспомогательный метод для выполнения GET-запросов к Zoho API.
     * Автоматически добавляет заголовок авторизации.
     *
     * @param string $endpoint Часть URL API после базового домена (например, '/inventory/api/v1/items').
     * @param array $query Параметры запроса.
     * @return array|null Результат запроса (JSON-ответ в виде массива) или null в случае ошибки.
     */
    protected function zohoApiGet(string $endpoint, array $query = []): ?array
    {
        // Получаем актуальный access_token через ZohoAuthService.
        $token = $this->zohoAuthService->getToken();

        // Если токен не получен (например, нет авторизации или произошла ошибка обновления),
        // логируем ошибку и возвращаем null.
        if (!$token) {
            Log::error("Failed to get Zoho access token for API GET request to {$endpoint}.");
            return null;
        }

        try {
            // Отправляем GET-запрос с заголовком авторизации и JSON-типом контента.
            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $token,
                'Content-Type' => 'application/json',
            ])->get($this->zohoApiDomain . $endpoint, $query);

            // Если запрос завершился ошибкой (статус не 2xx), логируем детали.
            if ($response->failed()) {
                Log::error("Zoho API GET request failed for {$endpoint}.", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'query' => $query
                ]);
                return null;
            }

            // Возвращаем JSON-ответ в виде массива.
            return $response->json();
        } catch (\Exception $e) {
            // Логируем любые исключения, возникшие в процессе выполнения запроса.
            Log::error("Exception during Zoho API GET request to {$endpoint}.", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Вспомогательный метод для выполнения POST-запросов к Zoho API.
     * Автоматически добавляет заголовок авторизации.
     *
     * @param string $endpoint Часть URL API после базового домена (например, '/inventory/api/v1/salesorders').
     * @param array $data Данные для отправки в теле запроса (будут преобразованы в JSON).
     * @return array|null Результат запроса (JSON-ответ в виде массива) или null в случае ошибки.
     */
    protected function zohoApiPost(string $endpoint, array $data): ?array
    {
        // Получаем актуальный access_token через ZohoAuthService.
        $token = $this->zohoAuthService->getToken();

        // Если токен не получен, логируем ошибку и возвращаем null.
        if (!$token) {
            Log::error("Failed to get Zoho access token for API POST request to {$endpoint}.");
            return null;
        }

        try {
            // Отправляем POST-запрос с заголовком авторизации и JSON-телом.
            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $token,
                'Content-Type' => 'application/json',
            ])->post($this->zohoApiDomain . $endpoint, $data);

            // Если запрос завершился ошибкой, логируем детали.
            if ($response->failed()) {
                Log::error("Zoho API POST request failed for {$endpoint}.", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'data' => $data
                ]);
                return null;
            }

            // Возвращаем JSON-ответ в виде массива.
            return $response->json();
        } catch (\Exception $e) {
            // Логируем любые исключения.
            Log::error("Exception during Zoho API POST request to {$endpoint}.", ['error' => $e->getMessage()]);
            return null;
        }
    }
}

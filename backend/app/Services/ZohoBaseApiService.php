<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Базовый абстрактный сервис для взаимодействия с Zoho API.
 *
 * Предоставляет вспомогательные методы для выполнения GET и POST запросов,
 * автоматически добавляя заголовок авторизации и ID организации Zoho.
 * Зависит от ZohoAuthService для получения актуального токена.
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
     * Идентификатор организации Zoho Inventory, необходимый для большинства запросов.
     * @var string
     */
    protected string $organizationId;

    /**
     * Конструктор ZohoBaseApiService.
     *
     * @param ZohoAuthService $zohoAuthService Автоматически инжектируется контейнером Laravel.
     */
    public function __construct(ZohoAuthService $zohoAuthService)
    {
        $this->zohoAuthService = $zohoAuthService;
        // Получаем базовый домен Zoho API из переменных окружения.
        $this->zohoApiDomain = env('ZOHO_API_DOMAIN', 'https://inventory.zoho.eu');

        // Получаем ID организации из переменных окружения.
        $this->organizationId = env('ZOHO_ORGANIZATION_ID');

        // Если ID организации не установлен, выбрасываем исключение.
        if (empty($this->organizationId)) {
            throw new \Exception("ZOHO_ORGANIZATION_ID is not set in the .env file. Please check your .env configuration.");
        }
    }

    /**
     * Вспомогательный метод для выполнения GET-запросов к Zoho API.
     * Автоматически добавляет заголовок авторизации и ID организации.
     *
     * @param string $endpoint Часть URL API после базового домена (например, '/api/v1/items').
     * @param array $query Параметры запроса.
     * @return array|null Результат запроса (JSON-ответ в виде массива) или null в случае ошибки.
     */
    protected function zohoApiGet(string $endpoint, array $query = []): ?array
    {
        // Получаем актуальный access_token через ZohoAuthService.
        $token = $this->zohoAuthService->getToken();

        // Если токен не получен, логируем ошибку и возвращаем null.
        if (!$token) {
            Log::error("Failed to get Zoho access token for API GET request to {$endpoint}.");
            return null;
        }

        try {
            // Формируем общие заголовки для всех запросов Zoho Inventory.
            $headers = [
                'Authorization' => 'Zoho-oauthtoken ' . $token,
                'Content-Type' => 'application/json',
            ];

            // Добавляем обязательный параметр с ID организации Zoho Inventory.
            $query['organization_id'] = $this->organizationId;

            // Отправляем GET-запрос.
            $response = Http::withHeaders($headers)
                ->get($this->zohoApiDomain . $endpoint, $query);

            // Если запрос завершился ошибкой (статус не 2xx), логируем детали.
            if ($response->failed()) {
                Log::error("Zoho API GET request failed for {$endpoint}.", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'query' => $query,
                    'endpoint' => $endpoint,
                    'headers' => $headers
                ]);
                return null;
            }

            // Возвращаем JSON-ответ в виде массива.
            return $response->json();
        } catch (\Exception $e) {
            // Логируем любые исключения, возникшие в процессе выполнения запроса.
            Log::error("Exception during Zoho API GET request to {$endpoint}.", ['error' => $e->getMessage(), 'endpoint' => $endpoint]);
            return null;
        }
    }

    /**
     * Вспомогательный метод для выполнения POST-запросов к Zoho API.
     * Автоматически добавляет заголовок авторизации и ID организации.
     *
     * @param string $endpoint Часть URL API после базового домена (например, '/api/v1/salesorders').
     * @param array $data Данные для отправки в теле запроса (будут преобразованы в JSON).
     * @return array|null Результат запроса (JSON-ответ в виде массива) или null в случае ошибки.
     */
    /*protected function zohoApiPost(string $endpoint, array $data): ?array
    {
        // Получаем актуальный access_token через ZohoAuthService.
        $token = $this->zohoAuthService->getToken();

        // Если токен не получен, логируем ошибку и возвращаем null.
        if (!$token) {
            Log::error("Failed to get Zoho access token for API POST request to {$endpoint}.");
            return null;
        }

        try {
            // Формируем общие заголовки для всех запросов Zoho Inventory.
            $headers = [
                'Authorization' => 'Zoho-oauthtoken ' . $token,
                'Content-Type' => 'application/json',
            ];

            // Добавляем обязательный параметр с ID организации Zoho Inventory.
            $query['organization_id'] = $this->organizationId;

            // Отправляем POST-запрос с JSON-телом.
            $response = Http::withHeaders($headers)
                ->post($this->zohoApiDomain . $endpoint, $data);

            // Если запрос завершился ошибкой, логируем детали.
            if ($response->failed()) {
                Log::error("Zoho API POST request failed for {$endpoint}.", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'data' => $data,
                    'endpoint' => $endpoint,
                    'headers' => $headers // Логируем заголовки для отладки
                ]);
                return null;
            }

            // Возвращаем JSON-ответ в виде массива.
            return $response->json();
        } catch (\Exception $e) {
            // Логируем любые исключения.
            Log::error("Exception during Zoho API POST request to {$endpoint}.", ['error' => $e->getMessage(), 'endpoint' => $endpoint]);
            return null;
        }
    }*/

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
            // Формируем общие заголовки для всех запросов Zoho Inventory.
            $headers = [
                'Authorization' => 'Zoho-oauthtoken ' . $token,
                'Content-Type' => 'application/json',
                // X-com-zoho-inventory-organizationid НЕ НУЖЕН ЗДЕСЬ,
                // так как organization_id будет в параметрах URL.
            ];

            // Формируем параметры запроса для URL (только organization_id)
            $queryParams = [
                'organization_id' => $this->organizationId,
            ];

            // Отправляем POST-запрос с JSON-телом.
            // Http::post позволяет передать параметры запроса вторым аргументом, а тело - третьим,
            // ИЛИ можно собрать URL вручную.
            // Используем ручную сборку URL для ясности и точного соответствия curl.
            $url = $this->zohoApiDomain . $endpoint . '?' . http_build_query($queryParams);

            $response = Http::withHeaders($headers)
                ->post($url, $data); // $data - это тело JSON, $url содержит параметры запроса

            // Если запрос завершился ошибкой, логируем детали.
            if ($response->failed()) {
                Log::error("Zoho API POST request failed for {$endpoint}.", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'data' => $data,
                    'endpoint' => $endpoint,
                    'headers' => $headers,
                    'url_sent' => $url // Добавляем логирование фактического URL
                ]);
                return null;
            }

            // Возвращаем JSON-ответ в виде массива.
            return $response->json();
        } catch (\Exception $e) {
            // Логируем любые исключения.
            Log::error("Exception during Zoho API POST request to {$endpoint}.", ['error' => $e->getMessage(), 'endpoint' => $endpoint]);
            return null;
        }
    }
}

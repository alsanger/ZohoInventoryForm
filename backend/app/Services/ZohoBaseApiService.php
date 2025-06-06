<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class ZohoBaseApiService
{
    protected string $zohoApiDomain;
    protected ZohoAuthService $zohoAuthService;
    protected string $organizationId;

    public function __construct(ZohoAuthService $zohoAuthService)
    {
        $this->zohoAuthService = $zohoAuthService;
        $this->zohoApiDomain = env('ZOHO_API_DOMAIN', 'https://inventory.zoho.eu');
        $this->organizationId = env('ZOHO_ORGANIZATION_ID');

        if (empty($this->organizationId)) {
            throw new \Exception("ZOHO_ORGANIZATION_ID is not set in the .env file. Please check your .env configuration.");
        }
    }

    /**
     * Выполнить GET-запрос к Zoho API.
     */
    protected function zohoApiGet(string $endpoint, array $query = []): ?array
    {
        $token = $this->zohoAuthService->getToken();

        if (!$token) {
            Log::error("Не удалось получить токен Zoho для GET запроса к {$endpoint}.");
            return null;
        }

        try {
            $headers = [
                'Authorization' => 'Zoho-oauthtoken ' . $token,
                'Content-Type' => 'application/json',
            ];

            $query['organization_id'] = $this->organizationId;

            $response = Http::withHeaders($headers)
                ->get($this->zohoApiDomain . $endpoint, $query);

            if ($response->failed()) {
                Log::error("GET запрос к Zoho API завершился ошибкой для {$endpoint}.", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'query' => $query,
                    'endpoint' => $endpoint,
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Исключение при GET запросе к Zoho API {$endpoint}.", ['error' => $e->getMessage(), 'endpoint' => $endpoint]);
            return null;
        }
    }

    /**
     * Выполнить POST-запрос к Zoho API.
     */
    protected function zohoApiPost(string $endpoint, array $data): ?array
    {
        $token = $this->zohoAuthService->getToken();

        if (!$token) {
            Log::error("Не удалось получить токен Zoho для POST запроса к {$endpoint}.");
            return null;
        }

        try {
            $headers = [
                'Authorization' => 'Zoho-oauthtoken ' . $token,
                'Content-Type' => 'application/json',
            ];

            $queryParams = [
                'organization_id' => $this->organizationId,
            ];

            $url = $this->zohoApiDomain . $endpoint . '?' . http_build_query($queryParams);

            $response = Http::withHeaders($headers)
                ->post($url, $data);

            if ($response->failed()) {
                Log::error("POST запрос к Zoho API завершился ошибкой для {$endpoint}.", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'data' => $data,
                    'endpoint' => $endpoint,
                    'url_sent' => $url
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Исключение при POST запросе к Zoho API {$endpoint}.", ['error' => $e->getMessage(), 'endpoint' => $endpoint]);
            return null;
        }
    }
}

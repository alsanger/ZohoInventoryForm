<?php
/*
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

    protected function zohoApiPut(string $endpoint, array $data): ?array
    {
        $token = $this->zohoAuthService->getToken();

        if (!$token) {
            Log::error("Не удалось получить токен Zoho для PUT запроса к {$endpoint}.");
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
                ->put($url, $data);

            if ($response->failed()) {
                Log::error("PUT запрос к Zoho API завершился ошибкой для {$endpoint}.", [
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
            Log::error("Исключение при PUT запросе к Zoho API {$endpoint}.", ['error' => $e->getMessage(), 'endpoint' => $endpoint]);
            return null;
        }
    }
}*/

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoBaseApiService
{
    protected string $zohoApiDomain;
    protected ZohoAuthService $zohoAuthService;
    protected string $organizationId;

    // Builder-параметры
    protected string $method = 'get';
    protected string $endpoint = '';
    protected array $query = [];
    protected ?array $body = null;
    protected array $headers = [
        'Content-Type' => 'application/json',
    ];

    // Singleton: статическая переменная для хранения единственного экземпляра
    private static ?self $instance = null;

    /**
     * Получить единственный экземпляр сервиса (Singleton).
     */
    public static function getInstance(ZohoAuthService $zohoAuthService = null): self
    {
        if (!self::$instance) {
            self::$instance = new self($zohoAuthService ?? app(ZohoAuthService::class));
        }
        return self::$instance;
    }

    private function __construct(ZohoAuthService $zohoAuthService)
    {
        $this->zohoAuthService = $zohoAuthService;
        $this->zohoApiDomain = env('ZOHO_API_DOMAIN', 'https://inventory.zoho.eu');
        $this->organizationId = env('ZOHO_ORGANIZATION_ID');

        if (empty($this->organizationId)) {
            throw new \Exception("ZOHO_ORGANIZATION_ID is not set in the .env file. Please check your .env configuration.");
        }

        // Добавляем organization_id как обязательный query-параметр
        $this->query['organization_id'] = $this->organizationId;
    }

    /**
     * Установить HTTP-метод.
     */
    public function setMethod(string $method): self
    {
        $this->method = strtolower($method);
        return $this;
    }

    /**
     * Установить эндпоинт API.
     */
    public function setEndpoint(string $endpoint): self
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    /**
     * Добавить query-параметр.
     */
    public function setQuery(string $key, $value): self
    {
        $this->query[$key] = $value;
        return $this;
    }

    /**
     * Установить массив query-параметров.
     */
    public function setQueryArray(array $query): self
    {
        $this->query = array_merge($this->query, $query);
        return $this;
    }

    /**
     * Установить тело запроса.
     */
    public function setBody(?array $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Добавить кастомный заголовок.
     */
    public function addHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Выполнить запрос.
     */
    public function build(): ?array
    {
        $token = $this->zohoAuthService->getToken();

        if (!$token) {
            Log::error("Не удалось получить токен Zoho для {$this->method} запроса к {$this->endpoint}.");
            return null;
        }

        try {
            $this->headers['Authorization'] = 'Zoho-oauthtoken ' . $token;
            $url = $this->zohoApiDomain . $this->endpoint;

            $httpClient = Http::withHeaders($this->headers);

            $response = match (strtolower($this->method)) {
                'get' => $httpClient->get($url, $this->query),
                'post' => $httpClient->post($url . '?' . http_build_query($this->query), $this->body),
                'put' => $httpClient->put($url . '?' . http_build_query($this->query), $this->body),
                'delete' => $httpClient->delete($url, $this->query),
                default => throw new \Exception("Unsupported HTTP method: {$this->method}")
            };

            if ($response->failed()) {
                Log::error("{$this->method} запрос к Zoho API завершился ошибкой для {$this->endpoint}.", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'query' => $this->query,
                    'body' => $this->body,
                    'endpoint' => $this->endpoint,
                    'url_sent' => $url
                ]);
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Исключение при {$this->method} запросе к Zoho API {$this->endpoint}.", [
                'error' => $e->getMessage(),
                'endpoint' => $this->endpoint
            ]);
            return null;
        }
    }

    /**
     * Сбросить параметры Builder для нового запроса.
     */
    public function reset(): self
    {
        $this->method = 'get';
        $this->endpoint = '';
        $this->query = ['organization_id' => $this->organizationId];
        $this->body = null;
        $this->headers = ['Content-Type' => 'application/json'];
        return $this;
    }

    // Предотвратить клонирование и сериализацию
    private function __clone()
    {
    }

    public function __wakeup()
    {
    }
}

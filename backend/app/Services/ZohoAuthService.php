<?php

namespace App\Services;

use App\Models\ZohoToken; // Импортируем модель ZohoToken
use Carbon\Carbon;          // Импортируем Carbon для работы с датами
use Illuminate\Support\Facades\Http; // Для выполнения HTTP-запросов
use Illuminate\Support\Facades\Log;  // Для логирования

/**
 * Сервис для управления аутентификацией Zoho OAuth 2.0.
 *
 * Отвечает за получение URL авторизации, обработку callback-запроса,
 * а также за получение и обновление access_token и refresh_token.
 * Токены хранятся в базе данных через модель ZohoToken.
 */
class ZohoAuthService
{
    /**
     * Zoho OAuth client identifier.
     * Идентификатор клиента Zoho OAuth, полученный из Zoho Developer Console.
     * @var string
     */
    private string $clientId;

    /**
     * Zoho OAuth client secret.
     * Секрет клиента Zoho OAuth, полученный из Zoho Developer Console.
     * @var string
     */
    private string $clientSecret;

    /**
     * OAuth authorization redirect URI.
     * URL, на который Zoho перенаправит пользователя после авторизации.
     * Должен точно совпадать с URI, указанным в Zoho Developer Console.
     * @var string
     */
    private string $redirectUri;

    /**
     * Base domain for Zoho accounts (для авторизации).
     * Базовый домен для Zoho Accounts (например, https://accounts.zoho.eu),
     * используется для OAuth авторизации.
     * @var string
     */
    private string $zohoAccountsDomain;

    /**
     * Инициализирует новый экземпляр ZohoAuthService.
     * Загружает необходимые конфигурационные параметры из переменных окружения.
     */
    public function __construct()
    {
        // Загружаем Client ID, Client Secret и Redirect URI из .env файла.
        $this->clientId = env('ZOHO_CLIENT_ID');
        $this->clientSecret = env('ZOHO_CLIENT_SECRET');
        $this->redirectUri = env('ZOHO_REDIRECT_URI');
        // Загружаем домен Zoho Accounts. Если переменная не задана, используем дефолт для EU.
        $this->zohoAccountsDomain = env('ZOHO_ACCOUNTS_DOMAIN', 'https://accounts.zoho.eu');
    }

    /**
     * Возвращает URL для начала процесса авторизации Zoho OAuth 2.0.
     *
     * Пользователь будет перенаправлен на этот URL для предоставления приложению
     * необходимых разрешений (scope) на доступ к своему аккаунту Zoho.
     *
     * @return string Сформированный URL авторизации.
     */
    public function getAuthUrl(): string
    {
        // 'scope' определяет, к каким данным и операциям будет иметь доступ наше приложение.
        // 'ZohoInventory.FullAccess.all' предоставляет полный доступ ко всем функциям Zoho Inventory.
        // Для продакшена рекомендуется использовать более гранулированные скоупы
        // (например, ZohoInventory.salesorders.CREATE, ZohoInventory.items.READ, ZohoInventory.contacts.ALL).
        return $this->zohoAccountsDomain . '/oauth/v2/auth?' . http_build_query([
                'scope' => 'ZohoInventory.FullAccess.all', // Скоуп для Zoho Inventory
                'client_id' => $this->clientId,             // Ваш Client ID
                'response_type' => 'code',                   // Запрашиваем код авторизации
                'access_type' => 'offline',                  // Для получения refresh_token (долгосрочного доступа)
                'redirect_uri' => $this->redirectUri,        // URL перенаправления после авторизации
                'prompt' => 'consent'                        // Запрашиваем согласие пользователя, даже если уже давал
            ]);
    }

    /**
     * Обрабатывает обратный вызов (callback) от Zoho OAuth авторизации.
     *
     * Принимает код авторизации, полученный от Zoho, обменивает его на access_token
     * и refresh_token, а затем сохраняет эти токены в базе данных.
     *
     * @param string $code Код авторизации, полученный от Zoho API.
     * @param string $location Регион Zoho API (например, 'eu', 'com', 'in'). По умолчанию 'eu'.
     * @return array Результат обработки авторизации: ['success' => bool, 'message' => string].
     */
    public function processCallback(string $code, string $location = 'eu'): array
    {
        try {
            // Формируем домен аккаунтов Zoho в зависимости от указанного региона.
            $accountsDomain = 'https://accounts.zoho.' . $location;

            // Параметры для POST-запроса на получение токенов.
            $params = [
                'grant_type' => 'authorization_code', // Тип гранта: обмен кода на токены
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'code' => $code,
            ];

            // Отправляем POST-запрос к Zoho Accounts API для обмена кода на токены.
            $response = Http::asForm()->post($accountsDomain . '/oauth/v2/token', $params);
            $data = $response->json(); // Парсим JSON-ответ.

            // Проверяем ответ на наличие ошибок или отсутствие access_token.
            if (!$data || isset($data['error']) || !isset($data['access_token'])) {
                $errorMessage = $data['error'] ?? 'Error getting token';
                Log::error('Zoho authorization error during callback', ['details' => $errorMessage, 'response' => $data]);
                return ['success' => false, 'message' => 'Ошибка авторизации: ' . $errorMessage];
            }

            // Очищаем таблицу от старых токенов, если мы поддерживаем только один набор токенов.
            // Это гарантирует, что в базе всегда будет только один актуальный набор токенов.
            ZohoToken::truncate();

            // Создаем новую запись с токенами в базе данных.
            ZohoToken::create([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                // Рассчитываем время истечения токена: текущее время + expires_in (в секундах).
                'expires_at' => Carbon::now()->addSeconds($data['expires_in']),
            ]);

            Log::info('Zoho Inventory authorization successful.');
            return ['success' => true, 'message' => 'Авторизация в Zoho Inventory прошла успешно!'];
        } catch (\Exception $e) {
            // Логируем любые исключения, возникшие в процессе обработки callback.
            Log::error('Zoho authorization exception during callback', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Ошибка авторизации: ' . $e->getMessage()];
        }
    }

    /**
     * Получает актуальный access_token.
     *
     * Проверяет наличие токена в базе данных. Если токен истек или скоро истечет,
     * пытается обновить его с использованием refresh_token.
     *
     * @return string|null Актуальный access_token или null, если не удалось получить/обновить токен.
     */
    public function getToken(): ?string
    {
        // Пытаемся получить самую новую запись токена из базы данных.
        $token = ZohoToken::first();

        // Если токена нет, значит авторизация еще не была пройдена.
        if (!$token) {
            Log::warning('No Zoho token found in database. Authorization required.');
            return null;
        }

        // Проверяем, истек ли текущий access_token или он близок к истечению.
        // Используем 30-секундный буфер, чтобы обновить токен чуть раньше,
        // предотвращая его истечение во время выполнения запроса.
        if ($token->expires_at->lt(Carbon::now()->addSeconds(30))) {
            Log::info('Zoho access token is expired or near expiration, attempting refresh.');
            // Если токен истек, пытаемся его обновить.
            $newAccessToken = $this->refreshToken($token);
            if ($newAccessToken) {
                Log::info('Zoho access token successfully refreshed.');
            } else {
                Log::error('Failed to refresh Zoho access token. Authorization may be required again.');
            }
            return $newAccessToken;
        }

        // Если access_token действителен, возвращаем его.
        return $token->access_token;
    }

    /**
     * Обновляет access_token Zoho с использованием refresh_token.
     *
     * Этот метод вызывается автоматически методом getToken(), когда access_token истекает.
     *
     * @param ZohoToken $token Текущая модель токена, которую нужно обновить.
     * @return string|null Новый access_token или null в случае ошибки обновления.
     */
    protected function refreshToken(ZohoToken $token): ?string
    {
        try {
            // Параметры для POST-запроса на обновление токена.
            $params = [
                'grant_type' => 'refresh_token',    // Тип гранта: обновление токена
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $token->refresh_token, // Используем refresh_token
            ];

            // Отправляем POST-запрос к Zoho Accounts API для получения нового access_token.
            $response = Http::asForm()->post($this->zohoAccountsDomain . '/oauth/v2/token', $params);
            $data = $response->json();

            // Проверяем ответ на наличие ошибок или отсутствие access_token.
            if (!$data || isset($data['error']) || !isset($data['access_token'])) {
                Log::error('Zoho token refresh error', ['error' => $data['error'] ?? 'Unknown error', 'response' => $data]);
                return null;
            }

            // Обновляем текущую запись токена в базе данных новым access_token и временем истечения.
            $token->access_token = $data['access_token'];
            // Устанавливаем время истечения с небольшим буфером (30 секунд),
            // чтобы обновить токен чуть раньше, чем он фактически истечет.
            $token->expires_at = Carbon::now()->addSeconds($data['expires_in'] - 30);
            $token->save(); // Сохраняем изменения.

            return $token->access_token;
        } catch (\Exception $e) {
            // Логируем любые исключения, возникшие в процессе обновления токена.
            Log::error('Exception while refreshing Zoho token', ['error' => $e->getMessage()]);
            return null;
        }
    }
}

# Инструкции по установке Zoho Inventory Integration

## Установка

1.  Клонируйте репозиторий:
    ```bash
    git clone https://github.com/alsanger/ZohoInventoryForm.git
    cd TestZoho
    ```

2.  Установка бэкенда (Laravel):
    ```bash
    cd backend
    composer install
    cp .env.example .env
    php artisan key:generate
    php artisan migrate
    cd ..
    ```

3.  Установка фронтенда (Vue.js):
    ```bash
    cd frontend
    npm install
    # Установка дополнительных модулей, если они не были установлены с npm install
    npm install vue-router pinia axios
    npm run build
    # Для разработки используйте npm run dev
    cd ..
    ```

4.  Запуск серверов:
    ```bash
    # Запуск бэкенда (в папке TestZoho/backend)
    cd backend
    php artisan serve
    
    # Запуск фронтенда (в папке TestZoho/frontend, в отдельном терминале)
    cd frontend
    npm run dev
    ```

## Настройка `.env` файлов

### Бэкенд (`TestZoho/backend/.env`)

```env
ZOHO_CLIENT_ID=ВАШ_ZOHO_CLIENT_ID_ЗДЕСЬ
ZOHO_CLIENT_SECRET=ВАШ_ZOHO_CLIENT_SECRET_ЗДЕСЬ
ZOHO_REDIRECT_URI=http://127.0.0.1:8000/zoho/callback
ZOHO_ACCOUNTS_DOMAIN=https://accounts.zoho.eu
ZOHO_API_DOMAIN=https://www.zohoapis.eu
ZOHO_ORGANIZATION_ID=ВАШ_ZOHO_ORGANIZATION_ID
ZOHO_DEFAULT_VENDOR_ID=ВАШ_ZOHO_DEFAULT_VENDOR_ID # Опционально
ZOHO_FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS="localhost:5173,127.0.0.1:5173"

DB_DATABASE=имя_вашей_бд
DB_USERNAME=ваш_пользователь
DB_PASSWORD=ваш_пароль
```

### Фронтенд (`TestZoho/frontend/.env`)

```env
VITE_BACKEND_BASE_URL=http://localhost:8000
VITE_API_BASE_URL="${VITE_BACKEND_BASE_URL}/api"
```

## Настройка Zoho API Console

1.  Создайте **Server-based Applications** клиент в [Zoho API Console](https://api-console.zoho.eu/).
2.  Установите **Homepage URL:** `http://localhost:5173`
3.  Установите **Authorized Redirect URIs:** `http://127.0.0.1:8000/zoho/callback`
4.  Скопируйте полученные `Client ID` и `Client Secret` в `.env` бэкенда
5.  Добавьте ваш **Zoho Organization ID** в `.env` бэкенда

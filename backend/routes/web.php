<?php
/*
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZohoAuthController;


Route::get('/zoho/auth', [ZohoAuthController::class, 'redirectToZohoAuth'])->name('zoho.auth');

// Маршрут для обработки callback-запроса от Zoho OAuth после авторизации.
// Zoho перенаправляет пользователя на этот URL с кодом авторизации.
Route::get('/zoho/callback', [ZohoAuthController::class, 'handleZohoCallback'])->name('zoho.callback');

// Корневой маршрут для базовой проверки работы бэкенда, или для отдачи какой-либо информации о API.
Route::get('/', function () {
    return response()->json(['message' => 'Laravel API is running.']);
});*/


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZohoAuthController;

// Маршрут для получения CSRF-куки Laravel Sanctum.
// Фронтенд (main.js) вызывает его при старте.
Route::get('/sanctum/csrf-cookie', function () {
    // response()->noContent() возвращает HTTP 204 No Content, что является стандартом для таких запросов.
    return response()->noContent();
});

// Маршрут для обработки callback-запроса от Zoho OAuth после авторизации.
// Zoho перенаправляет браузер на этот URL с кодом авторизации.
// Этот маршрут находится в web.php, так как он напрямую обрабатывает редирект браузера
// от внешнего сервиса (Zoho) и затем перенаправляет на фронтенд.
Route::get('/zoho/callback', [ZohoAuthController::class, 'handleZohoCallback'])->name('zoho.callback');

// Заглушка для корневого адреса, если кто-то обратится напрямую к бэкенду.
Route::get('/', function () {
    return 'Laravel Backend is running. Access frontend on ' . env('ZOHO_FRONTEND_URL', 'http://localhost:5173');
});

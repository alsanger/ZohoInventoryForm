<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZohoAuthController;

// Маршрут для получения CSRF-куки Laravel Sanctum
Route::get('/sanctum/csrf-cookie', function () {
    return response()->noContent();
});

// Маршрут для обработки callback-запроса от Zoho OAuth после авторизации
Route::get('/zoho/callback', [ZohoAuthController::class, 'handleZohoCallback'])->name('zoho.callback');

// Заглушка для корневого адреса, если кто-то обратится напрямую к бэкенду.
Route::get('/', function () {
    return 'Laravel Backend is running. Access frontend on ' . env('ZOHO_FRONTEND_URL', 'http://localhost:5173');
});

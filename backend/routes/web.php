<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZohoAuthController;


Route::get('/zoho/auth', [ZohoAuthController::class, 'redirectToZohoAuth'])->name('zoho.auth');

// Маршрут для обработки callback-запроса от Zoho OAuth после авторизации.
// Zoho перенаправляет пользователя на этот URL с кодом авторизации.
Route::get('/zoho/callback', [ZohoAuthController::class, 'handleZohoCallback'])->name('zoho.callback');

// Корневой маршрут для базовой проверки работы бэкенда, или для отдачи какой-либо информации о API.
Route::get('/', function () {
    return response()->json(['message' => 'Laravel API is running.']);
});

<?php
/*
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UtilController;
use App\Http\Controllers\Api\ZohoContactController;
use App\Http\Controllers\Api\ZohoItemController;
use App\Http\Controllers\Api\ZohoSalesOrderController;

// Эти маршруты не требуют аутентификации
//Route::get('/csrf-token', [UtilController::class, 'getCsrfToken']);
Route::get('/zoho/auth-status', [UtilController::class, 'checkZohoAuthStatus']);

// Защищенные маршруты (если нужна аутентификация)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/zoho/contacts', [ZohoContactController::class, 'index']);
    Route::post('/zoho/contacts', [ZohoContactController::class, 'store']);
    Route::get('/zoho/items', [ZohoItemController::class, 'index']);
    Route::post('/zoho/sales-orders', [ZohoSalesOrderController::class, 'store']);
});*/


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UtilController;
use App\Http\Controllers\ZohoAuthController;

// <-- Добавлен импорт ZohoAuthController
use App\Http\Controllers\Api\ZohoContactController;
use App\Http\Controllers\Api\ZohoItemController;
use App\Http\Controllers\Api\ZohoSalesOrderController;

// Эти маршруты не требуют аутентификации
// Маршрут для получения URL авторизации Zoho
Route::get('/zoho/auth', [ZohoAuthController::class, 'getZohoAuthUrl'])->name('api.zoho.auth');
// Маршрут для проверки статуса авторизации Zoho
Route::get('/zoho/auth-status', [UtilController::class, 'checkZohoAuthStatus']);

// Защищенные маршруты (требуют аутентификации Sanctum)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/zoho/contacts', [ZohoContactController::class, 'index']);
    Route::post('/zoho/contacts', [ZohoContactController::class, 'store']);
    Route::get('/zoho/items', [ZohoItemController::class, 'index']);
    Route::post('/zoho/sales-orders', [ZohoSalesOrderController::class, 'store']);
});

// Заглушка для корневого API, если кто-то обратится напрямую
Route::get('/', function () {
    return response()->json(['message' => 'Laravel API is running.']);
});

<?php

use App\Http\Controllers\Api\ZohoVendorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UtilController;
use App\Http\Controllers\ZohoAuthController;

use App\Http\Controllers\Api\ZohoContactController;
use App\Http\Controllers\Api\ZohoItemController;
use App\Http\Controllers\Api\ZohoSalesOrderController;
use App\Http\Controllers\Api\ZohoPurchaseOrderController;

// Эти маршруты не требуют аутентификации
// Маршрут для получения URL авторизации Zoho
Route::get('/zoho/auth', [ZohoAuthController::class, 'getZohoAuthUrl'])->name('api.zoho.auth');
// Маршрут для проверки статуса авторизации Zoho
Route::get('/zoho/auth-status', [UtilController::class, 'checkZohoAuthStatus']);

// Защищенные маршруты (требуют аутентификации Zoho)
Route::middleware(['zoho.auth'])->group(function () {
    Route::get('/zoho/contacts', [ZohoContactController::class, 'index']);
    Route::post('/zoho/contacts', [ZohoContactController::class, 'store']);
    Route::get('/zoho/items', [ZohoItemController::class, 'index']);
    Route::post('/zoho/sales-orders', [ZohoSalesOrderController::class, 'store']);
    Route::post('/zoho/purchase-orders', [ZohoPurchaseOrderController::class, 'store']);
    Route::get('/zoho/vendors', [ZohoVendorController::class, 'index']);
});


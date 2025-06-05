<?php

use App\Http\Controllers\Api\SalesPurchaseOrderController;
use App\Http\Controllers\Api\ZohoVendorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UtilController;
use App\Http\Controllers\ZohoAuthController;
use App\Http\Controllers\Api\ZohoContactController;
use App\Http\Controllers\Api\ZohoItemController;

// Группируем все Zoho маршруты с префиксом
Route::prefix('zoho')->name('api.zoho.')->group(function () {

    // Публичные маршруты
    Route::get('/auth', [ZohoAuthController::class, 'getZohoAuthUrl'])->name('auth');
    Route::get('/auth-status', [UtilController::class, 'checkZohoAuthStatus'])->name('auth-status');

    // Защищенные маршруты
    Route::middleware(['zoho.auth'])->group(function () {
        Route::get('/contacts', [ZohoContactController::class, 'index'])->name('contacts.index');
        Route::post('/contacts', [ZohoContactController::class, 'store'])->name('contacts.store');
        Route::get('/items', [ZohoItemController::class, 'index'])->name('items.index');
        Route::get('/vendors', [ZohoVendorController::class, 'index'])->name('vendors.index');
        Route::post('/sales-purchase-orders', [SalesPurchaseOrderController::class, 'store'])->name('orders.store');
    });
});

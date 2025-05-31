<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// Импортируем все контроллеры API, которые мы создали
use App\Http\Controllers\Api\UtilController;
use App\Http\Controllers\Api\ZohoContactController;
use App\Http\Controllers\Api\ZohoItemController;
use App\Http\Controllers\Api\ZohoSalesOrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Здесь вы можете зарегистрировать API-маршруты для вашего приложения. Эти
| маршруты загружаются RouteServiceProvider, и все они будут
| назначены группе middleware "api". Отличная работа!
|
*/

// Маршруты, которые не требуют предварительной авторизации Zoho.
// Они используются для базовой настройки фронтенда или проверки статуса.
Route::get('/csrf-token', [UtilController::class, 'getCsrfToken']);
Route::get('/zoho/auth-status', [UtilController::class, 'checkZohoAuthStatus']);

// Группа маршрутов API, которые требуют наличия действительного токена Zoho.
// Проверка токена Zoho реализована в конструкторах соответствующих контроллеров
// (ZohoContactController, ZohoItemController, ZohoSalesOrderController).
Route::middleware('api')->group(function () {
    // Маршруты для работы с контактами Zoho
    // GET /api/zoho/contacts - Получить список контактов
    // POST /api/zoho/contacts - Создать новый контакт
    Route::get('/zoho/contacts', [ZohoContactController::class, 'index']);
    Route::post('/zoho/contacts', [ZohoContactController::class, 'store']);

    // Маршруты для работы с товарами Zoho
    // GET /api/zoho/items - Получить список товаров
    Route::get('/zoho/items', [ZohoItemController::class, 'index']);

    // Маршруты для работы с заказами на продажу Zoho
    // POST /api/zoho/sales-orders - Создать новый заказ на продажу
    Route::post('/zoho/sales-orders', [ZohoSalesOrderController::class, 'store']);

    // Если в будущем потребуется прямой API для заказов на закупку, он будет здесь.
    // Route::post('/zoho/purchase-orders', [ZohoPurchaseOrderController::class, 'store']);
});

// Пример маршрута для проверки пользователя (если бы у нас была пользовательская авторизация в Laravel)
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZohoAuthController; // Импортируем наш новый Auth Controller

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Маршрут для перенаправления на Zoho OAuth для авторизации.
// Фронтенд будет вызывать этот URL (например, по нажатию кнопки "Авторизоваться").
Route::get('/zoho/auth', [ZohoAuthController::class, 'redirectToZohoAuth'])->name('zoho.auth');

// Маршрут для обработки callback-запроса от Zoho OAuth после авторизации.
// Zoho перенаправляет пользователя на этот URL с кодом авторизации.
Route::get('/zoho/callback', [ZohoAuthController::class, 'handleZohoCallback'])->name('zoho.callback');

// Оставим корневой маршрут для базовой проверки работы бэкенда,
// или для отдачи какой-либо информации о API.
Route::get('/', function () {
    return response()->json(['message' => 'Laravel API is running.']);
});

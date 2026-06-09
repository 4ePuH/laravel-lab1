<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API-маршруты авторизации
|--------------------------------------------------------------------------
| Все маршруты ниже получают префикс /api (см. bootstrap/app.php),
| плюс групповой префикс /auth — итого /api/auth/*.
*/
Route::prefix('auth')->group(function (): void {
    // Публичные маршруты.
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    // Регистрация — только для неавторизованных.
    Route::post('register', [AuthController::class, 'register'])->middleware('guest.token');

    // Маршруты только для авторизованных (проверка access-токена).
    Route::middleware('auth.token')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('out', [AuthController::class, 'out']);
        Route::get('tokens', [AuthController::class, 'tokens']);
        Route::post('out_all', [AuthController::class, 'outAll']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
});

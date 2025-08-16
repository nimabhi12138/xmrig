<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\Admin\CoinController as AdminCoinController;
use App\Http\Controllers\Admin\CoinFieldController as AdminCoinFieldController;
use App\Http\Controllers\User\CoinBrowseController;

Route::prefix('auth')->group(function () {
	Route::post('register', [AuthController::class, 'register']);
	Route::post('login', [AuthController::class, 'login']);
});

Route::get('healthz', [\App\Http\Controllers\HealthController::class, 'healthz']);

// Public config endpoint by token and user id
Route::middleware(['token','throttle:60,1'])->get('/config/{user_id}', [ConfigController::class, 'publicConfig']);

// Authenticated routes (token middleware sets user)
Route::middleware('token')->group(function () {
	Route::get('auth/me', [AuthController::class, 'me']);
	Route::get('coins', [CoinBrowseController::class, 'listCoins']);
	Route::get('coins/{coin_id}/fields', [CoinBrowseController::class, 'listFields']);
	Route::post('coins/{coin_id}/values', [CoinBrowseController::class, 'saveValues']);
	Route::get('me/config', [ConfigController::class, 'myConfig']);
});

// Admin routes (simplified, reuse token auth and check role in controller or future middleware)
Route::middleware(['token','admin'])->prefix('admin')->group(function () {
	Route::get('coins', [AdminCoinController::class, 'index']);
	Route::post('coins', [AdminCoinController::class, 'store']);
	Route::get('coins/{id}', [AdminCoinController::class, 'show']);
	Route::put('coins/{id}', [AdminCoinController::class, 'update']);
	Route::delete('coins/{id}', [AdminCoinController::class, 'destroy']);

	Route::get('coins/{coin_id}/fields', [AdminCoinFieldController::class, 'index']);
	Route::post('coins/{coin_id}/fields', [AdminCoinFieldController::class, 'store']);
	Route::put('coins/{coin_id}/fields/{field_id}', [AdminCoinFieldController::class, 'update']);
	Route::delete('coins/{coin_id}/fields/{field_id}', [AdminCoinFieldController::class, 'destroy']);

	Route::put('users/{user_id}/default-coin', [\App\Http\Controllers\Admin\UserAdminController::class, 'setDefaultCoin']);
	Route::put('users/{user_id}/role', [\App\Http\Controllers\Admin\UserAdminController::class, 'setRole']);
});

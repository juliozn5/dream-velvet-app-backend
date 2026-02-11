<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WalletController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Auth Público
Route::get('/ping', function () {
    return response()->json(['message' => 'PONG', 'time' => now()->toIso8601String()]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas Protegidas (Auth Sanctum)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Wallet
    Route::get('/wallet', [WalletController::class, 'index']); // Balance
    Route::get('/wallet/history', [WalletController::class, 'transactions']);
    Route::post('/wallet/purchase', [WalletController::class, 'purchase']); // Mock

    // Feed (Mock)
    Route::get('/feed', [\App\Http\Controllers\Api\FeedController::class, 'index']);

    // TODO: Mensajería y Feed (Fases siguientes)
});

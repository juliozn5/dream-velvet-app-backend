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

    // Búsqueda
    Route::get('/models', [\App\Http\Controllers\Api\SearchController::class, 'models']);

    Route::get('/chat', [\App\Http\Controllers\Api\ChatController::class, 'index']); // Lista de conversaciones
    Route::get('/chat/{userId}', [\App\Http\Controllers\Api\ChatController::class, 'getMessages']);
    Route::post('/chat', [\App\Http\Controllers\Api\ChatController::class, 'sendMessage']);

    // Obtener detalles de usuario (para el chat)
    Route::get('/users/{id}', function ($id) {
        return \App\Models\User::select('id', 'name', 'avatar', 'role')->findOrFail($id);
    });

    Route::get('/users/{id}', function ($id) {
        return \App\Models\User::select('id', 'name', 'avatar', 'role')->findOrFail($id);
    });
});

// Debug Pusher (Público)
Route::get('/debug-pusher', function () {
    try {
        $user = \App\Models\User::first() ?? new \App\Models\User(['id' => 1, 'name' => 'Test']);
        $msg = new \App\Models\Message([
            'sender_id' => $user->id,
            'receiver_id' => $user->id,
            'content' => 'DEBUG PUSHER ' . now()
        ]);
        // Usamos broadcast(new ...);
        event(new \App\Events\MessageSent($msg));
        return ['status' => 'Sent', 'env_cluster' => env('PUSHER_APP_CLUSTER'), 'env_key' => substr(env('PUSHER_APP_KEY'), 0, 5) . '...'];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
});

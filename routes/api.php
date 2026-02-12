<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\SearchController;

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
    Route::get('/feed', [FeedController::class, 'index']);

    // Búsqueda
    Route::get('/models', [SearchController::class, 'models']);

    // Chat
    Route::get('/chat', [ChatController::class, 'index']); // Lista de conversaciones
    Route::get('/chat/{userId}', [ChatController::class, 'getMessages']);
    Route::post('/chat', [ChatController::class, 'sendMessage']);

    // Obtener detalles de usuario (para el chat)
    Route::get('/users/{id}', function ($id) {
        return \App\Models\User::select('id', 'name', 'avatar', 'role')->findOrFail($id);
    });

    // Notificaciones
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unread_count']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
});

// Debug Pusher (Público) con ID dinámico
Route::get('/debug-pusher', function (Request $request) {
    try {
        $id = $request->query('id', 1);
        $user = \App\Models\User::find($id)
            ?? new \App\Models\User(['id' => $id, 'name' => 'TargetUser']);

        $msg = new \App\Models\Message([
            'sender_id' => 999123,
            'receiver_id' => $user->id,
            'content' => 'DEBUG MSG ' . now()->toTimeString()
        ]);

        // Disparar evento
        broadcast(new \App\Events\MessageSent($msg));

        return [
            "status" => "Enviado a chat.{$user->id}",
            "cluster" => env('PUSHER_APP_CLUSTER'),
            "event_name" => (new \App\Events\MessageSent($msg))->broadcastAs()
        ];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
});

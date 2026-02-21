<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SupportTicketController;

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

Route::middleware(['auth:sanctum'])->group(function () {
    // Ruta MANUAL de Auth para Pusher con DEBUG y Canal Explícito
    Route::post('/broadcasting/auth', function (Illuminate\Http\Request $request) {
        $user = $request->user();

        \Log::info("PUSHER AUTH HIT:", [
            'user' => $user ? $user->id : 'GUEST',
            'socket_id' => $request->socket_id,
            'channel_name' => $request->channel_name
        ]);

        // Definir el canal AQUÍ MISMO para asegurar que existe
        Illuminate\Support\Facades\Broadcast::channel('App.Models.User.{id}', function ($u, $id) {
            return (int) $u->id === (int) $id;
        });

        try {
            $response = Illuminate\Support\Facades\Broadcast::auth($request);
            \Log::info("PUSHER AUTH RESPONSE:", ['content' => $response]);
            return $response;
        } catch (\Throwable $e) {
            \Log::error("PUSHER AUTH ERROR: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Wallet
    Route::get('/wallet', [WalletController::class, 'index']); // Balance
    Route::get('/wallet/history', [WalletController::class, 'transactions']);
    Route::post('/wallet/purchase', [WalletController::class, 'purchase']); // Mock
    Route::post('/wallet/unlock-chat', [WalletController::class, 'unlockChat']);

    // Profile Update
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);

    // Feed (Mock)
    Route::get('/feed', [FeedController::class, 'index']);

    // Búsqueda
    Route::get('/models', [SearchController::class, 'models']);

    Route::get('/fast-content', [App\Http\Controllers\Api\FastContentController::class, 'index']);
    Route::post('/fast-content', [App\Http\Controllers\Api\FastContentController::class, 'store']);
    Route::delete('/fast-content/{id}', [App\Http\Controllers\Api\FastContentController::class, 'destroy']);

    // Chat
    Route::get('/chat', [ChatController::class, 'index']); // Lista de conversaciones
    Route::get('/chat/{userId}', [ChatController::class, 'getMessages']);
    Route::post('/chat', [ChatController::class, 'sendMessage']);
    Route::delete('/chat/{userId}', [ChatController::class, 'destroy']);

    // Obtener detalles de usuario (para el chat)
    Route::get('/users/{id}', function ($id) {
        return \App\Models\User::select('id', 'name', 'avatar', 'role')->findOrFail($id);
    });

    // Notificaciones
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unread_count']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);

    // Soporte / Tickets
    Route::get('/support/categories', [SupportTicketController::class, 'categories']);
    Route::get('/support/tickets', [SupportTicketController::class, 'index']);
    Route::post('/support/tickets', [SupportTicketController::class, 'store']);
    Route::get('/support/tickets/{id}', [SupportTicketController::class, 'show']);
    Route::post('/support/tickets/{id}/reply', [SupportTicketController::class, 'reply']);
    Route::post('/support/tickets/{id}/close', [SupportTicketController::class, 'close']);
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

// Custom Broadcasting Auth Route for API (Sanctum)
Route::middleware('auth:sanctum')->post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
});

Route::middleware('auth:sanctum')->post('/messages/{id}/unlock', [App\Http\Controllers\Api\ChatController::class, 'unlockMessage']);

Route::get('/debug-wallets', function () {
    $users = \App\Models\User::with('wallet')->get();
    $data = $users->map(function ($u) {
        return [
            'user_id' => $u->id,
            'name' => $u->name,
            'wallet_id' => $u->wallet ? $u->wallet->id : 'NO WALLET',
            'balance' => $u->wallet ? $u->wallet->balance : 0,
            'wallet_user_id' => $u->wallet ? $u->wallet->user_id : 'NsA',
        ];
    });

    // Check duplicates
    $walletIds = $users->pluck('wallet.id')->filter();
    $duplicates = $walletIds->diffAssoc($walletIds->unique());

    return [
        'wallet_integrity_check' => $duplicates->isEmpty() ? 'OK' : 'FAIL - DUPLICATE WALLETS DETECTED',
        'duplicates' => $duplicates,
        'users_data' => $data
    ];
});

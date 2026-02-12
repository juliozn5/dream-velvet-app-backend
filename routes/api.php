<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;

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

// Auth publica
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Rutas protegidas
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth actions
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Chat
    Route::get('/chat', [ChatController::class, 'index']);
    Route::get('/chat/{userId}', [ChatController::class, 'getMessages']);
    Route::post('/chat', [ChatController::class, 'sendMessage']);

    // User Details (Usado por el chat para mostrar avatar)
    Route::get('/users/{id}', function ($id) {
        return \App\Models\User::select('id', 'name', 'avatar', 'role')->findOrFail($id);
    });

    // Rutas adicionales que vi en logs anteriores
    // Route::get('/models', [\App\Http\Controllers\Api\SearchController::class, 'models']);
});

// Debug Pusher (Público) con ID dinámico
Route::get('/debug-pusher', function (Request $request) {
    try {
        $id = $request->query('id', 1);
        $user = \App\Models\User::findOrFail($id);

        $msg = new \App\Models\Message([
            'sender_id' => 999999, // Fake sender ID
            'receiver_id' => $user->id,
            'content' => 'DEBUG MSG from Backend at ' . now()->toTimeString()
        ]);

        // Disparar evento
        broadcast(new \App\Events\MessageSent($msg));

        return [
            "status" => "Evento enviado a chat.{$user->id}",
            "msg_content" => $msg->content,
            "cluster" => env('PUSHER_APP_CLUSTER'),
            "event_class" => \App\Events\MessageSent::class,
        ];
    } catch (\Exception $e) {
        return [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
});

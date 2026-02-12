<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Búsqueda
    Route::get('/models', [\App\Http\Controllers\Api\SearchController::class, 'models']);

    Route::get('/chat', [\App\Http\Controllers\Api\ChatController::class, 'index']); // Lista de conversaciones
    Route::get('/chat/{userId}', [\App\Http\Controllers\Api\ChatController::class, 'getMessages']);
    Route::post('/chat', [\App\Http\Controllers\Api\ChatController::class, 'sendMessage']);

    Route::get('/users/{id}', function ($id) {
        return \App\Models\User::select('id', 'name', 'avatar', 'role')->findOrFail($id);
    });
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

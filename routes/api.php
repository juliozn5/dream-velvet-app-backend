<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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

// Auth Routes (Login)
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['Credenciales incorrectas.'],
        ]);
    }

    // Revocar tokens anteriores (opcional, para limpieza)
    // $user->tokens()->delete();

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
        'token_type' => 'Bearer',
    ]);
});

Route::post('/register', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
        'token_type' => 'Bearer',
    ]);
});

// Protected Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    });

    // Chat Controller
    Route::get('/chat', [\App\Http\Controllers\Api\ChatController::class, 'index']);
    Route::get('/chat/{userId}', [\App\Http\Controllers\Api\ChatController::class, 'getMessages']);
    Route::post('/chat', [\App\Http\Controllers\Api\ChatController::class, 'sendMessage']);

    // User Details
    Route::get('/users/{id}', function ($id) {
        return User::select('id', 'name', 'avatar', 'role')->findOrFail($id);
    });

    // Búsqueda (si existía)
    // Route::get('/models', [\App\Http\Controllers\Api\SearchController::class, 'models']);
});

// Debug Pusher (Público) con ID dinámico
Route::get('/debug-pusher', function (Request $request) {
    try {
        $id = $request->query('id', 1);
        $user = User::find($id)
            ?? new User(['id' => $id, 'name' => 'TargetUser']);

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

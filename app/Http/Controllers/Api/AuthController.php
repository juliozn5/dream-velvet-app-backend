<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registro de nuevo usuario (Cliente o Modelo)
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:cliente,modelo',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($request->name) . '&background=random',
        ]);

        // Crear wallet inicial vacía
        $user->wallet()->create(['balance' => 0]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('wallet'),
            'token' => $token,
        ], 201);
    }

    /**
     * Login - Retorna token Sanctum
     */
    public function login(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('LOGIN REQUEST RECEIVED', ['ip' => $request->ip(), 'data' => $request->all()]);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            \Illuminate\Support\Facades\Log::warning('LOGIN FAILED: Invalid credentials for ' . $request->email);
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        \Illuminate\Support\Facades\Log::info('LOGIN SUCCESS: User ID ' . $user->id);

        return response()->json([
            'user' => $user->load('wallet'),
            'token' => $token,
        ]);
    }

    /**
     * Logout - Revoca token actual
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada']);
    }

    /**
     * Perfil del usuario autenticado
     */
    public function user(Request $request)
    {
        return response()->json(
            $request->user()->load('wallet')
        );
    }
}

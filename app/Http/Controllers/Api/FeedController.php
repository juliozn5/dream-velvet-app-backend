<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        // Mock posts para ver algo en el feed
        return response()->json([
            'posts' => [
                [
                    'id' => 1,
                    'user' => ['name' => 'Valentina Rose'],
                    'created_at' => now()->toIso8601String(),
                    'coin_cost' => 0,
                    'caption' => 'Hola fans! ❤️ ¿Qué tal su día?',
                    'likes_count' => 120,
                    'liked' => false,
                    'comments_count' => 45
                ],
                [
                    'id' => 2,
                    'user' => ['name' => 'Admin User'],
                    'created_at' => now()->subHours(2)->toIso8601String(),
                    'coin_cost' => 50,
                    'caption' => 'Contenido exclusivo para suscriptores 🔒',
                    'likes_count' => 15,
                    'liked' => false,
                    'comments_count' => 2
                ],
                [
                    'id' => 3,
                    'user' => ['name' => 'Sophie'],
                    'created_at' => now()->subHours(5)->toIso8601String(),
                    'coin_cost' => 0,
                    'caption' => 'Gym time 💪',
                    'likes_count' => 89,
                    'liked' => true,
                    'comments_count' => 12
                ]
            ]
        ]);
    }
}

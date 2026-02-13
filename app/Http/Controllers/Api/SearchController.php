<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Buscar usuarios con rol 'modelo'
     */
    public function models(Request $request)
    {
        $query = User::where('role', 'modelo'); // 'modelo' en español según AuthController

        if ($request->has('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $models = $query->get()->map(function ($model) use ($request) {
            // Verificar si el usuario actual ha desbloqueado a este modelo
            $isUnlocked = \App\Models\ChatUnlock::where('user_id', $request->user()->id)
                ->where('model_id', $model->id)
                ->exists();

            // Si el precio es 0, asumimos desbloqueado (o se maneja en frotnend)
            // Pero mejor ser explícito
            if (($model->chat_price ?? 0) <= 0) {
                $isUnlocked = true;
            }

            $model->is_unlocked = $isUnlocked;
            return $model;
        });

        return response()->json($models);
    }
}

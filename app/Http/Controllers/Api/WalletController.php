<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\Transaction;

class WalletController extends Controller
{
    /**
     * Obtener balance actual y total ganado (para modelos)
     */
    public function index(Request $request)
    {
        // Asegurar que obtenemos la wallet del usuario autenticado por su ID explícito
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['balance' => 0]
        );

        return response()->json($wallet);
    }

    /**
     * Historial de transacciones paginado
     * Filtrar: tipo (ingress/egress/all), rango fechas
     */
    public function transactions(Request $request)
    {
        $wallet = $request->user()->wallet;

        $transactions = $wallet->transactions()
            ->latest()
            ->paginate(20);

        return response()->json($transactions);
    }

    /**
     * Simular compra de paquete (Mock para Frontend)
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'package_name' => 'required|string',
        ]);

        // Buscar wallet explícitamente por user_id
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['balance' => 0]
        );

        // Usamos nuestro modelo Wallet que ya tiene el helper deposit()
        $transaction = $wallet->deposit(
            $request->amount,
            'purchase',
            "Compra paquete: {$request->package_name}"
        );

        return response()->json([
            'message' => 'Compra exitosa',
            'balance' => $wallet->fresh()->balance,
        ]);
    }

    /**
     * Desbloquear chat con modelo
     */
    public function unlockChat(Request $request)
    {
        $request->validate([
            'model_id' => 'required|exists:users,id',
        ]);

        $user = $request->user();
        $model = \App\Models\User::findOrFail($request->model_id);

        if (!$model->isModel()) {
            return response()->json(['error' => 'El usuario no es modelo'], 400);
        }

        if ($user->id === $model->id) {
            return response()->json(['error' => 'No puedes desbloquearte a ti mismo'], 400);
        }

        // Verificar si ya está desbloqueado
        $alreadyUnlocked = \App\Models\ChatUnlock::where('user_id', $user->id)
            ->where('model_id', $model->id)
            ->exists();

        if ($alreadyUnlocked) {
            return response()->json(['message' => 'Chat ya desbloqueado', 'already_unlocked' => true]);
        }

        $price = $model->chat_price ?? 0;

        if ($price <= 0) {
            // Si es gratis, registrar unlock sin cobro
            \App\Models\ChatUnlock::create([
                'user_id' => $user->id,
                'model_id' => $model->id,
                'amount' => 0
            ]);
            return response()->json(['message' => 'Chat desbloqueado gratis']);
        }

        $wallet = $user->wallet;
        if (!$wallet || $wallet->balance < $price) {
            return response()->json(['error' => 'Saldo insuficiente'], 402);
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. Cobrar al usuario
            $wallet->withdraw($price, 'chat_unlock', "Desbloqueo de chat con {$model->name}");

            // 2. Acreditar al SISTEMA (Registro de ganancia de la plataforma)
            // NO se le deposita a la modelo directamente.
            \App\Models\SystemProfit::create([
                'user_id' => $user->id,
                'model_id' => $model->id,
                'amount' => $price,
                'source' => 'chat_unlock'
            ]);

            // 3. Registrar desbloqueo
            $chatUnlock = \App\Models\ChatUnlock::create([
                'user_id' => $user->id,
                'model_id' => $model->id,
                'amount' => $price
            ]);

            // 4. Registrar transacción de monedas (tracking detallado)
            \App\Models\CoinTransaction::create([
                'user_id' => $user->id,
                'model_id' => $model->id,
                'amount' => $price,
                'type' => 'chat_unlock',
                'reference_id' => $chatUnlock->id,
                'description' => "Desbloqueo de chat con {$model->name}",
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return response()->json(['message' => 'Chat desbloqueado exitosamente', 'balance' => $wallet->fresh()->balance]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['error' => 'Error al procesar desbloqueo: ' . $e->getMessage()], 500);
        }
    }
}

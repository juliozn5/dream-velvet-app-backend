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
        $wallet = $request->user()->wallet;

        // Si por alguna razón no tiene wallet, se la creamos
        if (!$wallet) {
            $wallet = $request->user()->wallet()->create(['balance' => 0]);
        }

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

        $wallet = $request->user()->wallet;

        // Usamos nuestro modelo Wallet que ya tiene el helper deposit()
        $transaction = $wallet->deposit(
            $request->amount,
            'purchase',
            "Compra paquete: {$request->package_name}"
        );

        return response()->json([
            'message' => 'Compra exitosa',
            'balance' => $wallet->fresh()->balance,
            'transaction' => $transaction,
        ]);
    }
}

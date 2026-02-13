<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Obtener lista de conversaciones recientes (Bandeja de Entrada)
     */
    public function index()
    {
        $userId = Auth::id();

        // Obtener todos los mensajes donde participo
        $conversations = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->get()
            // Agrupar por el ID del "otro" usuario
            ->groupBy(function ($message) use ($userId) {
                return $message->sender_id == $userId ? $message->receiver_id : $message->sender_id;
            })
            ->map(function ($messages) use ($userId) {
                $lastMessage = $messages->first();
                $otherUser = $lastMessage->sender_id == $userId ? $lastMessage->receiver : $lastMessage->sender;

                return [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'avatar' => $otherUser->avatar,
                    'last_message' => $lastMessage->content,
                    'time' => $lastMessage->created_at,
                    'unread' => $messages->where('receiver_id', $userId)->whereNull('read_at')->count(),
                ];
            })
            ->values(); // Resetear keys para que sea array JSON

        return response()->json($conversations);
    }

    /**
     * Enviar un mensaje
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'fast_content_id' => 'nullable|exists:fast_contents,id',
        ]);

        $sender = Auth::user();

        // Validar que se envíe o texto o contenido
        if (!$request->message && !$request->fast_content_id) {
            return response()->json(['error' => 'Message or content required'], 422);
        }

        // Si envía contenido rápido, verificar que le pertenezca
        if ($request->fast_content_id) {
            $content = \App\Models\FastContent::find($request->fast_content_id);
            if ($content->user_id !== $sender->id) {
                return response()->json(['error' => 'Unauthorized content access'], 403);
            }
        }

        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $request->receiver_id,
            'content' => $request->message ?? '', // Puede estar vacío si manda solo foto
            'fast_content_id' => $request->fast_content_id,
            'is_paid' => false, // Por defecto no pagado (si tiene precio > 0)
        ]);

        // Cargar relación para que el evento lleve la info del contenido
        $message->load(['sender', 'fastContent']);

        // Emitir evento a Pusher
        broadcast(new MessageSent($message));

        // Enviar notificación
        $receiver = User::find($request->receiver_id);
        if ($receiver) {
            $receiver->notify(new \App\Notifications\NewMessageNotification($message));
        }

        return response()->json([
            'status' => 'Message Sent!',
            'message' => $message
        ]);
    }

    /**
     * Obtener historial de chat con un usuario específico
     */
    public function getMessages($userId)
    {
        $myId = Auth::id();

        // Mensajes donde YO soy sender y ÉL receiver, O viceversa
        $messages = Message::where(function ($q) use ($myId, $userId) {
            $q->where('sender_id', $myId)->where('receiver_id', $userId);
        })->orWhere(function ($q) use ($myId, $userId) {
            $q->where('sender_id', $userId)->where('receiver_id', $myId);
        })
            ->orderBy('created_at', 'asc')
            ->with(['sender', 'receiver', 'fastContent'])
            ->get();

        // Marcar como leídos los mensajes que he recibido de este usuario
        Message::where('sender_id', $userId)
            ->where('receiver_id', $myId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }
    /**
     * Eliminar conversación y volver a bloquear chat
     */
    public function destroy($userId)
    {
        $myId = Auth::id();

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. Eliminar mensajes entre ambos usuarios
            Message::where(function ($q) use ($myId, $userId) {
                $q->where('sender_id', $myId)->where('receiver_id', $userId);
            })->orWhere(function ($q) use ($myId, $userId) {
                $q->where('sender_id', $userId)->where('receiver_id', $myId);
            })->delete();

            // 2. Revocar desbloqueo (ChatUnlock)
            // Borramos cualquier registro donde participate esta pareja, sin importar quién es quién en el unlock
            // (Para forzar que EL CLIENTE tenga que pagar de nuevo)
            \App\Models\ChatUnlock::where(function ($q) use ($myId, $userId) {
                $q->where('user_id', $myId)->where('model_id', $userId);
            })->orWhere(function ($q) use ($myId, $userId) {
                $q->where('user_id', $userId)->where('model_id', $myId);
            })->delete();

            \Illuminate\Support\Facades\DB::commit();

            return response()->json(['message' => 'Chat eliminado y bloqueado nuevamente']);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['error' => 'Error al eliminar chat'], 500);
        }
    }
    /**
     * Desbloquear mensaje (simulación de pago)
     */
    public function unlockMessage($id)
    {
        $message = Message::with(['fastContent', 'sender.wallet'])->findOrFail($id);

        if (!$message->fastContent) {
            return response()->json(['error' => 'Contenido no encontrado'], 404);
        }

        $user = Auth::user();
        $user->load('wallet'); // Cargar billetera

        // Verificar si ya está pagado
        if ($message->is_paid) {
            return response()->json(['message' => $message]);
        }

        $price = $message->fastContent->price;
        $balance = $user->wallet ? $user->wallet->balance : 0;

        if ($balance < $price) {
            return response()->json([
                'error' => 'Saldo insuficiente',
                'current_balance' => $balance,
                'required' => $price
            ], 402);
        }

        try {
            // Realizar transacción (usando modelo Wallet)
            if ($user->wallet) {
                $user->wallet->withdraw($price, 'unlock_content', "Desbloqueo mensaje #{$id}");
            }

            // NOTA: NO transferimos a la modelo. El dinero va al sistema.

            $message->update(['is_paid' => true]);

            return response()->json([
                'status' => 'Unlocked',
                'message' => $message->load(['sender', 'fastContent'])
            ]);

        } catch (\Exception $e) {
            Log::error("Unlock error: " . $e->getMessage());
            return response()->json(['error' => 'Error en la transacción: ' . $e->getMessage()], 500);
        }
    }
}

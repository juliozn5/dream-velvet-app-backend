<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'message' => 'required|string',
        ]);

        $sender = Auth::user();
        $receiverId = $request->receiver_id;

        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiverId,
            'content' => $request->message,
        ]);

        // Emitir evento a Pusher
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'status' => 'Message Sent!',
            'message' => $message->load('sender')
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
            ->with('sender')
            ->get();

        return response()->json($messages);
    }
}

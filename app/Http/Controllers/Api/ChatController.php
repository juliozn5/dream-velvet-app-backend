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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportTicketController extends Controller
{
    /**
     * Listar tickets del usuario autenticado
     */
    public function index(Request $request)
    {
        $tickets = SupportTicket::where('user_id', Auth::id())
            ->withCount('messages')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return response()->json($tickets);
    }

    /**
     * Crear un nuevo ticket de soporte
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'category' => 'nullable|string|in:general,billing,technical,account,report',
            'priority' => 'nullable|string|in:low,normal,high,critical',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => Auth::id(),
            'subject' => $request->subject,
            'description' => $request->description,
            'category' => $request->category ?? 'general',
            'priority' => $request->priority ?? 'normal',
            'status' => 'open',
        ]);

        // Crear el primer mensaje con la descripción
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $request->description,
            'is_admin_reply' => false,
        ]);

        return response()->json([
            'message' => 'Ticket creado exitosamente',
            'ticket' => $ticket->load('messages'),
        ], 201);
    }

    /**
     * Ver un ticket con sus mensajes
     */
    public function show($id)
    {
        $ticket = SupportTicket::where('user_id', Auth::id())
            ->with(['messages.user:id,name,avatar,role', 'assignedAdmin:id,name'])
            ->findOrFail($id);

        // Marcar como leídos los mensajes del admin
        TicketMessage::where('ticket_id', $ticket->id)
            ->where('is_admin_reply', true)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($ticket);
    }

    /**
     * Enviar un mensaje dentro de un ticket existente
     */
    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $ticket = SupportTicket::where('user_id', Auth::id())->findOrFail($id);

        // No permitir respuestas en tickets cerrados
        if ($ticket->isClosed()) {
            return response()->json(['error' => 'Este ticket está cerrado'], 403);
        }

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
            'is_admin_reply' => false,
        ]);

        // Si el ticket estaba resuelto, reabrirlo
        if ($ticket->status === 'resolved') {
            $ticket->update(['status' => 'open']);
        }

        $ticket->touch(); // Actualizar updated_at para que suba en la lista

        return response()->json([
            'message' => 'Mensaje enviado',
            'ticket_message' => $message->load('user:id,name,avatar,role'),
        ]);
    }

    /**
     * El usuario cierra su propio ticket (ya resuelto)
     */
    public function close($id)
    {
        $ticket = SupportTicket::where('user_id', Auth::id())->findOrFail($id);

        $ticket->markAsClosed();

        return response()->json(['message' => 'Ticket cerrado exitosamente']);
    }

    /**
     * Categorías disponibles (para mostrar en el frontend)
     */
    public function categories()
    {
        return response()->json(SupportTicket::categories());
    }
}

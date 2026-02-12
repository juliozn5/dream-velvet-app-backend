<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Models\Message;

class NewMessageNotification extends Notification
{
    use Queueable;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        // Se envía a base de datos y por broadcast (socket)
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'message',
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender->name ?? 'Usuario',
            'sender_avatar' => $this->message->sender->avatar ?? null,
            'content' => substr($this->message->content, 0, 50),
            'chat_id' => $this->message->sender_id,
        ];
    }

    public function toBroadcast($notifiable)
    {
        // Esto es lo que llega al Frontend en tiempo real
        return new BroadcastMessage([
            'id' => $this->id,
            'data' => $this->toDatabase($notifiable),
            'read_at' => null,
            'created_at' => now()->toIso8601String()
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Carbon\Carbon;

class SupportTicketSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('es_ES');

        // Obtener el admin
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            echo "No se encontró un admin. Saltando seeder de tickets...\n";
            return;
        }

        // Obtener usuarios normales (clientes y modelos)
        $users = User::where('role', '!=', 'admin')->get();
        if ($users->isEmpty()) {
            echo "No hay usuarios para crear tickets.\n";
            return;
        }

        echo "Generando tickets de soporte...\n";

        // Seleccionar algunos usuarios al azar para que tengan tickets
        $usersWithTickets = $users->random(min(8, $users->count()));

        $categories = array_keys(SupportTicket::categories());
        $priorities = array_keys(SupportTicket::priorities());
        $statuses = array_keys(SupportTicket::statuses());

        foreach ($usersWithTickets as $user) {
            // Cada usuario seleccionado tendrá 1 o 2 tickets
            $numTickets = rand(1, 2);

            for ($i = 0; $i < $numTickets; $i++) {
                $status = $faker->randomElement($statuses);

                // Fechas aleatorias
                $createdDate = Carbon::now()->subDays(rand(1, 15))->subHours(rand(1, 24));
                $resolvedDate = null;
                $closedDate = null;
                $assignedTo = null;

                if (in_array($status, ['in_progress', 'resolved', 'closed'])) {
                    $assignedTo = $admin->id;
                }

                if ($status === 'resolved') {
                    $resolvedDate = (clone $createdDate)->addHours(rand(1, 24));
                } elseif ($status === 'closed') {
                    $resolvedDate = (clone $createdDate)->addHours(rand(1, 12));
                    $closedDate = (clone $resolvedDate)->addHours(rand(1, 24));
                }

                $ticket = SupportTicket::create([
                    'user_id' => $user->id,
                    'subject' => $faker->realText(50),
                    'description' => $faker->realText(200),
                    'category' => $faker->randomElement($categories),
                    'priority' => $faker->randomElement($priorities),
                    'status' => $status,
                    'assigned_to' => $assignedTo,
                    'resolved_at' => $resolvedDate,
                    'closed_at' => $closedDate,
                    'created_at' => $createdDate,
                    'updated_at' => $closedDate ?? $resolvedDate ?? $createdDate,
                ]);

                // Mensaje inicial del usuario
                TicketMessage::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'message' => $ticket->description,
                    'is_admin_reply' => false,
                    'created_at' => $createdDate,
                    'updated_at' => $createdDate,
                ]);

                // Si el ticket no es nuevo (open), agregar algunas respuestas
                if ($status !== 'open') {
                    // Respuesta del admin
                    $adminReplyDate = (clone $createdDate)->addMinutes(rand(30, 120));
                    TicketMessage::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $admin->id,
                        'message' => 'Hola, estamos revisando tu caso. ' . $faker->sentence(),
                        'is_admin_reply' => true,
                        'read_at' => (clone $adminReplyDate)->addMinutes(10), // Asumimos que el usuario lo leyó
                        'created_at' => $adminReplyDate,
                        'updated_at' => $adminReplyDate,
                    ]);

                    // Si está cerrado o resuelto, tal vez el usuario respondió
                    if (in_array($status, ['resolved', 'closed']) && rand(0, 1) === 1) {
                        $userReplyDate = (clone $adminReplyDate)->addMinutes(rand(10, 60));
                        TicketMessage::create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $user->id,
                            'message' => '¡Muchas gracias! Ya pude solucionarlo.',
                            'is_admin_reply' => false,
                            'created_at' => $userReplyDate,
                            'updated_at' => $userReplyDate,
                        ]);

                        // Admin cierra el caso
                        $adminFinalDate = (clone $userReplyDate)->addMinutes(rand(5, 30));
                        TicketMessage::create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $admin->id,
                            'message' => 'Excelente, procedemos a cerrar el ticket. ¡Que tengas buen día!',
                            'is_admin_reply' => true,
                            'created_at' => $adminFinalDate,
                            'updated_at' => $adminFinalDate,
                        ]);
                    }
                }
            }
        }

        echo "✅ Tickets de soporte y mensajes creados con éxito.\n";
    }
}

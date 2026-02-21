<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Usuario que abre el ticket
            $table->string('subject'); // Asunto del ticket
            $table->text('description'); // Descripción inicial del problema
            $table->string('category')->default('general'); // general, billing, technical, account, report
            $table->string('priority')->default('normal'); // low, normal, high, critical
            $table->string('status')->default('open'); // open, in_progress, resolved, closed
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // Admin asignado
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index('category');
            $table->index('user_id');
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Quien escribe (usuario o admin)
            $table->text('message');
            $table->boolean('is_admin_reply')->default(false); // true = respuesta del admin
            $table->string('attachment_url')->nullable(); // Adjunto opcional (captura de pantalla, etc)
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete(); // La wallet afectada
            $table->enum('type', ['deposit', 'withdrawal', 'purchase', 'message', 'call', 'tip', 'subscription', 'refund']);
            $table->integer('amount'); // Positivo (ingreso) o Negativo (gasto)
            $table->string('description')->nullable();

            // Relación opcional con otra entidad (quién envió/recibió la moneda)
            $table->unsignedBigInteger('related_user_id')->nullable();

            // Para trazabilidad extra (opcional)
            $table->string('reference_id')->nullable(); // ID de pasarela de pago (Stripe)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

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
        Schema::create('system_profits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // Quién gastó
            $table->foreignId('model_id')->nullable()->constrained('users'); // En quién gastó (opcional, por si es otro servicio)
            $table->integer('amount'); // Cantidad de monedas
            $table->string('source')->default('chat_unlock'); // Origen del ingreso (chat_unlock, gift, etc)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_profits');
    }
};

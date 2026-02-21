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
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Cliente que gastó
            $table->foreignId('model_id')->constrained('users')->cascadeOnDelete(); // Modelo en la que gastó
            $table->integer('amount'); // Cantidad de monedas gastadas
            $table->string('type'); // 'chat_unlock', 'content_unlock'
            $table->unsignedBigInteger('reference_id')->nullable(); // ID del ChatUnlock o Message
            $table->text('description')->nullable(); // Descripción opcional
            $table->timestamps();

            $table->index(['user_id', 'model_id']);
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};

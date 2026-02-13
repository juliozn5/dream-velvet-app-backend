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
        \DB::table('users')
            ->where('role', 'modelo')
            ->where('chat_price', 0)
            ->update(['chat_price' => 10]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

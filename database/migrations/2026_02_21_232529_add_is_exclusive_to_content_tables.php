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
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('is_exclusive')->default(false)->after('caption');
        });

        Schema::table('stories', function (Blueprint $table) {
            $table->boolean('is_exclusive')->default(false)->after('media_type');
        });

        Schema::table('highlights', function (Blueprint $table) {
            $table->boolean('is_exclusive')->default(false)->after('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('is_exclusive');
        });

        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn('is_exclusive');
        });

        Schema::table('highlights', function (Blueprint $table) {
            $table->dropColumn('is_exclusive');
        });
    }
};

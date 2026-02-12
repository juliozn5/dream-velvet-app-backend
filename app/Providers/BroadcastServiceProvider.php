<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::routes(['prefix' => 'api', 'middleware' => ['auth:sanctum']]);

        // En Laravel 11, routes/channels.php ya se carga en routes() o similar,
        // pero cargarlo aquí explícitamente evita problemas si `bootstrap/app.php` no lo hace bien.
        require base_path('routes/channels.php');
    }
}

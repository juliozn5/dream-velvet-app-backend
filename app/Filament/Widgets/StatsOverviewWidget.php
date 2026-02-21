<?php

namespace App\Filament\Widgets;

use App\Models\CoinTransaction;
use App\Models\ChatUnlock;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $totalModels = User::where('role', 'modelo')->count();
        $totalClients = User::where('role', 'cliente')->count();
        $totalCoinsSpent = CoinTransaction::sum('amount');
        $totalChatUnlocks = ChatUnlock::count();
        $totalContentUnlocks = CoinTransaction::where('type', 'content_unlock')->count();

        return [
            Stat::make('Total Usuarios', $totalUsers)
                ->description("$totalClients clientes · $totalModels modelos")
                ->descriptionIcon('heroicon-o-users')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Total Modelos', $totalModels)
                ->description('Creadoras de contenido activas')
                ->descriptionIcon('heroicon-o-star')
                ->color('success')
                ->chart([3, 5, 2, 8, 4, 6, 3]),

            Stat::make('Monedas Gastadas', number_format($totalCoinsSpent))
                ->description('Total acumulado del sistema')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('warning')
                ->chart([2, 4, 6, 8, 10, 8, 12]),

            Stat::make('Chats Desbloqueados', $totalChatUnlocks)
                ->description("$totalContentUnlocks contenidos desbloqueados")
                ->descriptionIcon('heroicon-o-lock-open')
                ->color('danger')
                ->chart([1, 3, 2, 5, 4, 6, 8]),
        ];
    }
}

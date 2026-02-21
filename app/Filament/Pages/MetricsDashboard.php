<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class MetricsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Métricas';

    protected static ?string $navigationGroup = 'Métricas';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Dashboard de Métricas';

    protected static string $view = 'filament.pages.metrics-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\TopModelsWidget::class,
            \App\Filament\Widgets\RecentTransactionsWidget::class,
        ];
    }
}

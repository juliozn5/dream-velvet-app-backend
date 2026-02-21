<?php

namespace App\Filament\Widgets;

use App\Models\CoinTransaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTransactionsWidget extends BaseWidget
{
    protected static ?string $heading = '📋 Últimas Transacciones de Monedas';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CoinTransaction::query()
                    ->with(['user', 'modelUser'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario (Gastó)')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('modelUser.name')
                    ->label('Modelo (Recibió)')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'chat_unlock' => 'warning',
                        'content_unlock' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'chat_unlock' => '🔓 Chat',
                        'content_unlock' => '📸 Contenido',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monedas')
                    ->numeric()
                    ->color('danger')
                    ->suffix(' 🪙'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}

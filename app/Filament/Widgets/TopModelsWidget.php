<?php

namespace App\Filament\Widgets;

use App\Models\CoinTransaction;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopModelsWidget extends BaseWidget
{
    protected static ?string $heading = '🏆 Modelos con Más Monedas Gastadas en Ellas';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('role', 'modelo')
                    ->withCount([
                        'coinTransactionsAsModel as total_coins' => function ($query) {
                            $query->select(\Illuminate\Support\Facades\DB::raw('COALESCE(SUM(amount), 0)'));
                        },
                        'coinTransactionsAsModel as chat_unlocks_count' => function ($query) {
                            $query->where('type', 'chat_unlock');
                        },
                        'coinTransactionsAsModel as content_unlocks_count' => function ($query) {
                            $query->where('type', 'content_unlock');
                        },
                    ])
                    ->orderByDesc('total_coins')
            )
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->rowIndex(),
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=random'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Modelo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_coins')
                    ->label('💰 Total Monedas')
                    ->numeric()
                    ->sortable()
                    ->color('warning')
                    ->weight('bold')
                    ->suffix(' 🪙'),
                Tables\Columns\TextColumn::make('chat_unlocks_count')
                    ->label('🔓 Chats')
                    ->numeric()
                    ->sortable()
                    ->color('info'),
                Tables\Columns\TextColumn::make('content_unlocks_count')
                    ->label('📸 Contenido')
                    ->numeric()
                    ->sortable()
                    ->color('success'),
                Tables\Columns\TextColumn::make('chat_price')
                    ->label('Precio Chat')
                    ->numeric()
                    ->suffix(' monedas'),
            ])
            ->defaultSort('total_coins', 'desc')
            ->paginated([5, 10, 25]);
    }
}

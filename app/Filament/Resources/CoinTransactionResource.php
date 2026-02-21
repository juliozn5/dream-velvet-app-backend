<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoinTransactionResource\Pages;
use App\Models\CoinTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CoinTransactionResource extends Resource
{
    protected static ?string $model = CoinTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Transacciones de Monedas';

    protected static ?string $navigationGroup = 'Métricas';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Transacción de Monedas';

    protected static ?string $pluralModelLabel = 'Transacciones de Monedas';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->disabled(),
                Forms\Components\Select::make('model_id')
                    ->label('Modelo')
                    ->relationship('modelUser', 'name')
                    ->disabled(),
                Forms\Components\TextInput::make('amount')
                    ->label('Monedas')
                    ->disabled(),
                Forms\Components\TextInput::make('type')
                    ->label('Tipo')
                    ->disabled(),
                Forms\Components\TextInput::make('description')
                    ->label('Descripción')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario (Gastó)')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('modelUser.name')
                    ->label('Modelo (Recibió)')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monedas')
                    ->numeric()
                    ->sortable()
                    ->color('danger')
                    ->suffix(' 🪙'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'chat_unlock' => 'warning',
                        'content_unlock' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'chat_unlock' => '🔓 Chat Unlock',
                        'content_unlock' => '📸 Content Unlock',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'chat_unlock' => 'Desbloqueo de Chat',
                        'content_unlock' => 'Desbloqueo de Contenido',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoinTransactions::route('/'),
        ];
    }
}

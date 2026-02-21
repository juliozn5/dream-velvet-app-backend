<?php

namespace App\Livewire;

use App\Models\CoinTransaction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class UserCoinTransactionsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public int $userId;

    public function mount(int $userId): void
    {
        $this->userId = $userId;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CoinTransaction::query()
                    ->where(function ($query) {
                        $query->where('user_id', $this->userId)
                            ->orWhere('model_id', $this->userId);
                    })
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
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25]);
    }

    public function render()
    {
        return view('livewire.user-coin-transactions-table');
    }
}

<?php

namespace App\Filament\Resources\CoinTransactionResource\Pages;

use App\Filament\Resources\CoinTransactionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCoinTransactions extends ListRecords
{
    protected static string $resource = CoinTransactionResource::class;

    public function getTabs(): array
    {
        return [
            'todas' => Tab::make('Todas')
                ->icon('heroicon-o-list-bullet')
                ->badge(fn() => \App\Models\CoinTransaction::count()),

            'chat_unlock' => Tab::make('Desbloqueos de Chat')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->badge(fn() => \App\Models\CoinTransaction::where('type', 'chat_unlock')->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'chat_unlock')),

            'content_unlock' => Tab::make('Desbloqueos de Contenido')
                ->icon('heroicon-o-photo')
                ->badge(fn() => \App\Models\CoinTransaction::where('type', 'content_unlock')->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'content_unlock')),
        ];
    }
}

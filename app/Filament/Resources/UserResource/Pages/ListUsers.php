<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos')
                ->icon('heroicon-o-users')
                ->badge(fn() => \App\Models\User::count()),

            'clientes' => Tab::make('Clientes')
                ->icon('heroicon-o-user')
                ->badge(fn() => \App\Models\User::where('role', 'cliente')->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('role', 'cliente')),

            'modelos' => Tab::make('Modelos')
                ->icon('heroicon-o-star')
                ->badge(fn() => \App\Models\User::where('role', 'modelo')->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('role', 'modelo')),
        ];
    }
}
